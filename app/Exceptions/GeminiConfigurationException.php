<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class GeminiConfigurationException extends Exception
{
    public function __construct(string $message = "Gemini API configuration is invalid. Please check your GEMINI_API_KEY in .env file.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
