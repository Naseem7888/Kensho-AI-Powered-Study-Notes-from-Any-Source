<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class GeminiApiException extends Exception
{
    protected ?array $responseData;
    protected ?int $statusCode;

    public function __construct(string $message = "Gemini API request failed.", int $code = 0, ?Throwable $previous = null, ?int $statusCode = null, ?array $responseData = null)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public static function fromHttpError(Throwable $exception, ?int $statusCode = null, ?array $responseData = null): self
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception, $statusCode, $responseData);
    }
}
