<?php

namespace App\Exceptions;

use Exception;

class InvalidYoutubeUrlException extends Exception
{
    protected string $url;

    public function __construct(string $url, ?string $message = null)
    {
        $this->url = $url;
        parent::__construct($message ?? "Invalid YouTube URL: {$url}. Please provide a valid YouTube video URL (e.g., https://youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID)");
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
