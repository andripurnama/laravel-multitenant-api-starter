<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when an authentication token has expired
 * 
 * Requirement 2.6: Token validation should provide clear error messages
 */
class TokenExpiredException extends AuthException
{
    protected $message = 'The authentication token has expired.';
    protected $code = 401;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message, $this->code);
    }
}
