<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

trait RespondsWithJson
{
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::created($data, $message);
    }

    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return ApiResponse::error($message, $status, $errors);
    }
}
