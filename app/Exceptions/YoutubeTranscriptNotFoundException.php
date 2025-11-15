<?php

namespace App\Exceptions;

use Exception;

class YoutubeTranscriptNotFoundException extends Exception
{
    protected string $videoId;
    protected array $attemptedLanguages;

    public function __construct(string $videoId, array $attemptedLanguages = [])
    {
        $this->videoId = $videoId;
        $this->attemptedLanguages = $attemptedLanguages;
        $languages = implode(', ', $attemptedLanguages);
        $message = sprintf(
            'No transcript found for YouTube video %s. The video may not have captions enabled, or the requested languages (%s) are not available.',
            $videoId,
            $languages
        );
        parent::__construct($message);
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public function getAttemptedLanguages(): array
    {
        return $this->attemptedLanguages;
    }
}
