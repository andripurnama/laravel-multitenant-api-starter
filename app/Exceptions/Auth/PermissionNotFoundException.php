<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

class PermissionNotFoundException extends AuthException
{
    protected $message = 'The specified permission does not exist.';

    protected $code = 404;
}
