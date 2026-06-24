<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use Exception;

/**
 * Base exception class for all authentication and authorization exceptions
 */
abstract class AuthException extends Exception
{
    /**
     * Get the HTTP status code for this exception
     */
    public function getStatusCode(): int
    {
        return $this->code ?: 500;
    }

    /**
     * Get the exception response data
     */
    public function getResponseData(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'code' => class_basename($this),
            ],
        ];
    }
}
