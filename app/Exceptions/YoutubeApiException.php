<?php

namespace App\Exceptions;

use Exception;

class YoutubeApiException extends Exception
{
    protected ?string $videoId = null;
    protected bool $isRateLimited = false;

    public function __construct(string $message = 'YouTube transcript fetching failed.', int $code = 0, ?\Throwable $previous = null, ?string $videoId = null, bool $isRateLimited = false)
    {
        parent::__construct($message, $code, $previous);
        $this->videoId = $videoId;
        $this->isRateLimited = $isRateLimited;
    }

    public function getVideoId(): ?string
    {
        return $this->videoId;
    }

    public function isRateLimited(): bool
    {
        return $this->isRateLimited;
    }

    public static function rateLimitExceeded(string $videoId): self
    {
        return new self("YouTube rate limit exceeded for video {$videoId}. Please try again later or use a proxy.", 429, null, $videoId, true);
    }
}
