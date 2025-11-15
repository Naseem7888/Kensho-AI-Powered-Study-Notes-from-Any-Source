<?php

namespace App\Exceptions;

use Exception;

class UnsupportedAudioFormatException extends Exception
{
    protected string $format;
    protected array $supportedFormats;

    public function __construct(string $format, array $supportedFormats = [])
    {
        $this->format = $format;
        $this->supportedFormats = $supportedFormats;
        $message = sprintf(
            "Audio format '%s' is not supported. Supported formats: %s",
            $format,
            implode(', ', $supportedFormats)
        );
        parent::__construct($message);
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }
}
