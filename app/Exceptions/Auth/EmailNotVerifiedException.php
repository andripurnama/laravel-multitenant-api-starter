<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when a user attempts to access resources without verifying their email
 * 
 * Requirement 9.2: Email verification should be enforced where required
 */
class EmailNotVerifiedException extends AuthException
{
    protected $message = 'Email address has not been verified.';
    protected $code = 403;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message, $this->code);
    }
}
