<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when a user lacks required permissions for an action
 * 
 * Requirement 10.2: Permission-based authorization should return 403 when denied
 */
class InsufficientPermissionsException extends AuthException
{
    protected $message = 'You do not have permission to perform this action.';
    protected $code = 403;

    public function __construct(?string $message = null, ?string $permission = null)
    {
        $finalMessage = $message ?? $this->message;
        
        if ($permission !== null) {
            $finalMessage = "You do not have the required permission: {$permission}";
        }
        
        parent::__construct($finalMessage, $this->code);
    }
}
