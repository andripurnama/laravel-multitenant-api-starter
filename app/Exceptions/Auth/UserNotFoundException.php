<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

/**
 * Exception thrown when a user cannot be found in the specified tenant
 * 
 * Requirement 5.4: Cross-tenant authentication should fail appropriately
 */
class UserNotFoundException extends AuthException
{
    protected $message = 'User not found in the specified tenant.';
    protected $code = 404;

    public function __construct(?string $message = null, ?int $userId = null)
    {
        $finalMessage = $message ?? $this->message;
        
        if ($userId !== null) {
            $finalMessage = "User with ID {$userId} not found in the specified tenant.";
        }
        
        parent::__construct($finalMessage, $this->code);
    }
}
