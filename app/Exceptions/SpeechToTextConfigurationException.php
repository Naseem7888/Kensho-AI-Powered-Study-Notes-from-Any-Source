<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class SpeechToTextConfigurationException extends Exception
{
    public function __construct(string $message = "Google Cloud Speech-to-Text configuration is invalid. Please check GOOGLE_APPLICATION_CREDENTIALS and GOOGLE_PROJECT_ID in .env file.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
