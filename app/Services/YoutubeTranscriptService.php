<?php

namespace App\Services;

use App\Exceptions\InvalidYoutubeUrlException;
use App\Exceptions\YoutubeApiException;
use App\Exceptions\YoutubeTranscriptNotFoundException;
use GuzzleHttp\Client as HttpClient;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Illuminate\Support\Facades\Log;
use MrMySQL\YoutubeTranscript\Exception\NoTranscriptFoundException;
use MrMySQL\YoutubeTranscript\Exception\TooManyRequestsException;
use MrMySQL\YoutubeTranscript\TranscriptListFetcher;

class YoutubeTranscriptService
{
    protected HttpClient $httpClient;
    protected RequestFactory $requestFactory;
    protected StreamFactory $streamFactory;

    public function __construct(HttpClient $httpClient, RequestFactory $requestFactory, StreamFactory $streamFactory)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function extractTranscript(string $youtubeUrl, array $preferredLanguages = ['en']): array
    {
        $videoId = $this->extractVideoId($youtubeUrl);

        try {
            $fetcher = new TranscriptListFetcher($this->httpClient, $this->requestFactory, $this->streamFactory);
            $list = $fetcher->fetch($videoId);

            $transcriptInfo = $list->findTranscript($preferredLanguages);
            $isGenerated = false;
            if (!$transcriptInfo) {
                $transcriptInfo = $list->findGeneratedTranscript($preferredLanguages);
                $isGenerated = true;
            }

            if (!$transcriptInfo) {
                throw new YoutubeTranscriptNotFoundException($videoId, $preferredLanguages);
            }

            // Package uses Transcript::fetch(bool $preserve_formatting = false)
            $transcript = $transcriptInfo->fetch();

            $segments = [];
            $textParts = [];
            foreach ($transcript as $seg) {
                $segments[] = [
                    'text' => $seg['text'] ?? '',
                    'start' => $seg['start'] ?? 0.0,
                    'duration' => $seg['duration'] ?? 0.0,
                ];
                $textParts[] = trim(($seg['text'] ?? ''));
            }

            // Language code is exposed as a public property `language_code` in package
            $language = $transcriptInfo->language_code ?? ($preferredLanguages[0] ?? 'en');

            return [
                'transcript' => trim(implode(' ', $textParts)),
                'language' => $language,
                'is_generated' => $isGenerated,
                'video_id' => $videoId,
                'segments' => $segments,
            ];
        } catch (TooManyRequestsException $e) {
            Log::warning('YouTube transcript rate limited', ['video' => $videoId]);
            throw YoutubeApiException::rateLimitExceeded($videoId);
        } catch (NoTranscriptFoundException $e) {
            throw new YoutubeTranscriptNotFoundException($videoId, $preferredLanguages);
        } catch (\Throwable $e) {
            Log::error('YouTube transcript fetch error', ['video' => $videoId, 'error' => $e->getMessage()]);
            throw new YoutubeApiException('Failed to fetch YouTube transcript: ' . $e->getMessage(), (int) $e->getCode(), $e, $videoId);
        }
    }

    public function extractVideoId(string $url): string
    {
        // If raw ID provided
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $patterns = [
            '/v=([a-zA-Z0-9_-]{11})/i',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/i',
            '/embed\/([a-zA-Z0-9_-]{11})/i',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        throw new InvalidYoutubeUrlException($url);
    }

    public function isValidYoutubeUrl(string $url): bool
    {
        try {
            $this->extractVideoId($url);
            return true;
        } catch (InvalidYoutubeUrlException) {
            return false;
        }
    }
}
