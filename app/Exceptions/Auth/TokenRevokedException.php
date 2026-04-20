<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when attempting to use a revoked token
 * 
 * Requirement 2.6: Token validation should provide clear error messages
 */
class TokenRevokedException extends AuthException
{
    protected $message = 'The authentication token has been revoked.';
    protected $code = 401;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message, $this->code);
    }
}
