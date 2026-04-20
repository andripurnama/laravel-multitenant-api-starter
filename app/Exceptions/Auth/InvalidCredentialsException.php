<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when authentication credentials are invalid
 * 
 * Requirement 2.6: Authentication error messages should not reveal which credential failed
 */
class InvalidCredentialsException extends AuthException
{
    protected $message = 'The provided credentials are invalid.';
    protected $code = 401;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message, $this->code);
    }
}
