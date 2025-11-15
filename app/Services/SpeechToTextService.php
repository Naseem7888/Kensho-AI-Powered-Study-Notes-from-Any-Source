<?php

namespace App\Services;

use App\Exceptions\SpeechToTextApiException;
use App\Exceptions\SpeechToTextConfigurationException;
use App\Exceptions\UnsupportedAudioFormatException;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechClient;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class SpeechToTextService
{
    protected string $credentialsPath;
    protected string $projectId;
    protected string $location;
    protected Filesystem $storage;

    public function __construct(Filesystem $storage)
    {
        $cfg = config('google.speech_to_text');
        $this->credentialsPath = (string) ($cfg['credentials'] ?? '');
        $this->projectId = (string) ($cfg['project_id'] ?? '');
        $this->location = (string) ($cfg['location'] ?? 'us-central1');
        $this->storage = $storage;

        if (empty($this->credentialsPath) || empty($this->projectId)) {
            throw new SpeechToTextConfigurationException();
        }

        if (!$this->credentialsFileExists($this->credentialsPath)) {
            throw new SpeechToTextConfigurationException("Credentials file not found at '{$this->credentialsPath}'.");
        }
    }

    public function transcribeAudioFile(string $filePath, string $languageCode = null): array
    {
        $cfg = config('google.speech_to_text');
        $languageCode = $languageCode ?: ($cfg['default_language'] ?? 'en-US');
        $supportedLanguages = $cfg['supported_languages'] ?? ['en-US'];
        $maxSize = $cfg['max_file_size'] ?? (10 * 1024 * 1024);
        $supportedFormats = $cfg['supported_formats'] ?? $this->getSupportedFormats();

        if (!in_array($languageCode, $supportedLanguages, true)) {
            throw new SpeechToTextApiException('Unsupported language code for transcription.', 0, null, $filePath, $languageCode);
        }

        try {
            $content = $this->storage->get($filePath);
        } catch (\Throwable $e) {
            throw new SpeechToTextApiException('Unable to read audio file from storage: ' . $e->getMessage(), 0, $e, $filePath, $languageCode);
        }

        // File size validation (best-effort if adapter supports size())
        try {
            if (method_exists($this->storage, 'size')) {
                $size = $this->storage->size($filePath);
                if ($size > $maxSize) {
                    throw new SpeechToTextApiException('Audio file exceeds maximum allowed size.', 0, null, $filePath, $languageCode);
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal if size cannot be determined
            Log::warning('Unable to determine audio file size', ['file' => $filePath, 'error' => $e->getMessage()]);
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $supportedFormats, true)) {
            throw new UnsupportedAudioFormatException($ext, $supportedFormats);
        }

        $encoding = $this->getEncodingForExtension($ext);

        $clientOptions = [
            'credentials' => $this->credentialsPath,
        ];

        try {
            $client = new SpeechClient($clientOptions);

            $config = (new RecognitionConfig())
                ->setEncoding($encoding)
                ->setLanguageCode($languageCode)
                ->setEnableAutomaticPunctuation(true)
                ->setEnableWordTimeOffsets(true);

            $audio = (new RecognitionAudio())
                ->setContent($content);

            $response = $client->recognize($config, $audio);

            $transcript = '';
            $confidences = [];
            $words = [];

            foreach ($response->getResults() as $result) {
                $alternative = $result->getAlternatives()[0] ?? null;
                if ($alternative) {
                    $transcript .= trim($alternative->getTranscript()) . ' ';
                    if ($alternative->hasConfidence()) {
                        $confidences[] = $alternative->getConfidence();
                    }
                    foreach ($alternative->getWords() as $w) {
                        $words[] = [
                            'word' => $w->getWord(),
                            'start' => $w->getStartTime()?->getSeconds() + ($w->getStartTime()?->getNanos() / 1e9),
                            'end' => $w->getEndTime()?->getSeconds() + ($w->getEndTime()?->getNanos() / 1e9),
                        ];
                    }
                }
            }

            $avgConfidence = count($confidences) ? array_sum($confidences) / count($confidences) : 0.0;

            $client->close();

            // Approximate duration: maximum end timestamp among words
            $duration = 0.0;
            foreach ($words as $w) {
                if (isset($w['end']) && $w['end'] > $duration) {
                    $duration = $w['end'];
                }
            }

            return [
                'transcript' => trim($transcript),
                'confidence' => $avgConfidence,
                'language' => $languageCode,
                'duration' => $duration, // Derived from word-level timestamps (approximate)
                'words' => $words,
            ];
        } catch (\Google\ApiCore\ApiException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            if ((int) $code === 403) {
                $message = 'Speech-to-Text quota exceeded or permission denied (403). Check project quotas and credentials.';
            }
            Log::error('Speech-to-Text API error', ['error' => $e->getMessage(), 'file' => $filePath, 'lang' => $languageCode]);
            throw SpeechToTextApiException::fromApiError(new \RuntimeException($message, (int) $code, $e), $filePath, $languageCode);
        } catch (\Throwable $e) {
            Log::error('Speech-to-Text general error', ['error' => $e->getMessage(), 'file' => $filePath, 'lang' => $languageCode]);
            throw new SpeechToTextApiException($e->getMessage(), (int) $e->getCode(), $e, $filePath, $languageCode);
        }
    }

    public function getSupportedFormats(): array
    {
        $cfg = config('google.speech_to_text');
        return $cfg['supported_formats'] ?? ['mp3', 'wav', 'flac', 'ogg', 'm4a'];
    }

    public function cleanupTemporaryFile(string $filePath): void
    {
        try {
            if ($this->storage->exists($filePath)) {
                $this->storage->delete($filePath);
                Log::info('Deleted temporary audio file', ['path' => $filePath]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to delete temporary audio file', ['path' => $filePath, 'error' => $e->getMessage()]);
        }
    }

    protected function credentialsFileExists(string $path): bool
    {
        if (is_file($path)) {
            return true;
        }
        $base = function_exists('base_path') ? base_path($path) : $path;
        return is_file($base);
    }

    protected function getEncodingForExtension(string $ext): int
    {
        return match ($ext) {
            'mp3', 'm4a' => AudioEncoding::MP3,
            'wav' => AudioEncoding::LINEAR16,
            'flac' => AudioEncoding::FLAC,
            'ogg' => AudioEncoding::OGG_OPUS,
            default => AudioEncoding::ENCODING_UNSPECIFIED,
        };
    }
}
