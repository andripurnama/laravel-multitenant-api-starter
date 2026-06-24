<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return self::respond(true, $message, $data, null, $status, $meta);
    }

    public static function created(
        mixed $data = null,
        ?string $message = null,
        array $meta = []
    ): JsonResponse {
        return self::success($data, $message, 201, $meta);
    }

    public static function error(
        string $message,
        int $status = 400,
        ?array $errors = null,
        array $meta = []
    ): JsonResponse {
        return self::respond(false, $message, null, $errors, $status, $meta);
    }

    private static function respond(
        bool $success,
        ?string $message,
        mixed $data,
        ?array $errors,
        int $status,
        array $meta
    ): JsonResponse {
        $payload = ['success' => $success];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = self::resolveData($data);
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    private static function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        return $data;
    }
}
