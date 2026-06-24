<?php

declare(strict_types=1);

use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('success returns standardized success payload', function () {
    $response = ApiResponse::success(['id' => 1], 'Done');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
            'success' => true,
            'message' => 'Done',
            'data' => ['id' => 1],
        ]);
});

test('success omits null message and data keys', function () {
    $response = ApiResponse::success();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
            'success' => true,
        ]);
});

test('created returns 201 status', function () {
    $response = ApiResponse::created(['id' => 1]);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getData(true))->toBe([
            'success' => true,
            'data' => ['id' => 1],
        ]);
});

test('error returns standardized error payload', function () {
    $response = ApiResponse::error('Something went wrong', 422, [
        'email' => ['The email field is required.'],
    ]);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe([
            'success' => false,
            'message' => 'Something went wrong',
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ]);
});

test('success resolves json resources into data', function () {
    $user = User::factory()->make([
        'id' => 1,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $response = ApiResponse::success(new UserResource($user));
    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['data']['id'])->toBe(1)
        ->and($data['data']['name'])->toBe('Jane Doe')
        ->and($data['data']['email'])->toBe('jane@example.com');
});

test('success resolves resource collections into data', function () {
    $users = User::factory()->count(2)->make();

    $response = ApiResponse::success(UserResource::collection($users));
    $data = $response->getData(true);

    expect($data['data'])->toHaveCount(2)
        ->and($data['data'][0])->toHaveKeys(['id', 'name', 'email']);
});

test('success includes meta when provided', function () {
    $response = ApiResponse::success(meta: ['page' => 1]);

    expect($response->getData(true))->toBe([
        'success' => true,
        'meta' => ['page' => 1],
    ]);
});
