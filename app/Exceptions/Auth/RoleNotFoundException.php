<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when a role cannot be found in the specified tenant
 * 
 * Requirement 9.2: Role-based authorization should handle missing roles appropriately
 */
class RoleNotFoundException extends AuthException
{
    protected $message = 'The specified role does not exist.';
    protected $code = 404;

    public function __construct(?string $message = null, ?string $roleName = null)
    {
        $finalMessage = $message ?? $this->message;
        
        if ($roleName !== null) {
            $finalMessage = "Role '{$roleName}' not found in the specified tenant.";
        }
        
        parent::__construct($finalMessage, $this->code);
    }
}
