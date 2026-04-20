<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when a password reset token is invalid or expired
 * 
 * Requirement 13.6: Password reset should validate tokens and provide clear errors
 */
class InvalidResetTokenException extends AuthException
{
    protected $message = 'The password reset token is invalid or expired.';
    protected $code = 400;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message, $this->code);
    }
}
