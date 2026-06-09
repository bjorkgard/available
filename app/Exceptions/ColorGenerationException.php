<?php

namespace App\Exceptions;

class ColorGenerationException extends \RuntimeException
{
    public function __construct(string $message = 'Unable to generate a sufficiently distinct color after 100 attempts.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
