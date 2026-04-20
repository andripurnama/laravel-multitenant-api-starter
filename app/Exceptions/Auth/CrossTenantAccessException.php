<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when attempting to access resources across tenant boundaries
 * 
 * Requirement 5.4: Cross-tenant access should be prevented and return appropriate errors
 */
class CrossTenantAccessException extends AuthException
{
    protected $message = 'Cross-tenant access is not permitted.';
    protected $code = 403;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message, $this->code);
    }
}
