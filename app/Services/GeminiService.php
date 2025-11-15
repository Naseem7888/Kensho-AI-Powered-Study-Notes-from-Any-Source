<?php

namespace App\Services;

use App\Exceptions\GeminiApiException;
use App\Exceptions\GeminiConfigurationException;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected float $temperature;
    protected int $maxTokens;
    protected int $timeout;
    protected HttpClient $http;

    public function __construct()
    {
        $config = config('google.gemini');
        if (empty($config['api_key'])) {
            throw new GeminiConfigurationException();
        }

        $this->apiKey = (string) $config['api_key'];
        $this->model = (string) ($config['model'] ?? 'gemini-2.5-flash');
        $this->temperature = (float) ($config['temperature'] ?? 0.7);
        $this->maxTokens = (int) ($config['max_tokens'] ?? 8192);
        $this->timeout = (int) ($config['timeout'] ?? 60);
        $this->http = new HttpClient([
            'base_uri' => 'https://generativelanguage.googleapis.com/',
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * @param string $content Transcript or text input
     * @param string $sourceType 'youtube' | 'audio' | 'text'
     * @return array
     * @throws GeminiApiException
     */
    public function generateStudyNotes(string $content, string $sourceType): array
    {
        $schema = $config['study_notes_schema'] ?? config('google.gemini.study_notes_schema');
        $prompt = $this->buildPrompt($content, $sourceType);

        $body = [
            'contents' => [[
                'role' => 'user',
                'parts' => [[ 'text' => $prompt ]],
            ]],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
                'response_mime_type' => 'application/json',
                'response_schema' => $schema,
            ],
        ];

        try {
            $response = $this->http->post("v1beta/models/{$this->model}:generateContent", [
                'query' => ['key' => $this->apiKey],
                'json' => $body,
            ]);
            $status = $response->getStatusCode();
            $data = json_decode((string) $response->getBody(), true);

            if ($status < 200 || $status >= 300) {
                throw new GeminiApiException('Gemini API returned non-success status.', $status, null, $status, $data);
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!$text) {
                throw new GeminiApiException('Gemini API returned an invalid response body.', $status, null, $status, $data);
            }

            $parsed = json_decode($text, true);
            if (!is_array($parsed)) {
                throw new GeminiApiException('Gemini did not return valid JSON content.', $status, null, $status, ['raw' => $text]);
            }
            return $parsed;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->getResponse() ? json_decode((string) $e->getResponse()->getBody(), true) : null;
            $message = $e->getMessage();
            if ($status === 429) {
                $message = 'Gemini API rate limit exceeded. Please try again later.';
            } elseif ($status === 403) {
                $message = 'Gemini API quota or permission error (403). Check your API key and project quotas.';
            }
            Log::error('Gemini API client error', ['status' => $status, 'body' => $body]);
            throw GeminiApiException::fromHttpError(new \RuntimeException($message, $status, $e), $status, $body);
        } catch (\Throwable $e) {
            Log::error('Gemini API error', ['error' => $e->getMessage()]);
            throw new GeminiApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->http->get("v1beta/models/{$this->model}", [
                'query' => ['key' => $this->apiKey],
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable $e) {
            throw new GeminiApiException('Failed to connect to Gemini API: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function buildPrompt(string $content, string $sourceType): string
    {
        $lines = [
            "You are a helpful study assistant. Analyze the following {$sourceType} content and produce structured study notes as JSON matching the provided schema: summary, key_concepts (with concept and explanation), study_questions (5-10), difficulty_level (beginner|intermediate|advanced), and estimated_study_time (minutes). Keep explanations concise and accurate.",
            '',
            'Content:',
            $content,
        ];
        return trim(implode("\n", $lines));
    }
}
