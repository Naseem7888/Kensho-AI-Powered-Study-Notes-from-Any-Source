<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class SpeechToTextApiException extends Exception
{
    protected ?string $filePath;
    protected ?string $languageCode;

    public function __construct(string $message = "Speech-to-Text API request failed.", int $code = 0, ?Throwable $previous = null, ?string $filePath = null, ?string $languageCode = null)
    {
        parent::__construct($message, $code, $previous);
        $this->filePath = $filePath;
        $this->languageCode = $languageCode;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public static function fromApiError(Throwable $exception, ?string $filePath = null, ?string $languageCode = null): self
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception, $filePath, $languageCode);
    }
}
