<?php

namespace App\Exceptions;

use RuntimeException;

class AuthServiceException extends RuntimeException
{
    public function __construct(
        protected string $errorCode,
        protected int $statusCode,
        string $message = ''
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
