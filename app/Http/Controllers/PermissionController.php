<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssignPermissionRequest;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Resources\RoleResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\PermissionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionServiceInterface $permissionService,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Assign role to user
     * 
     * Assign a role to a user within a specific tenant context.
     * 
     * @authenticated
     */
    public function assignRole(AssignRoleRequest $request): JsonResponse
    {
        $user = $this->userRepository->find($request->input('user_id'));

        $this->permissionService->assignRole(
            $user,
            $request->input('role'),
            $request->input('tenant_id')
        );

        return response()->json(['message' => 'Role assigned successfully']);
    }

    /**
     * Remove role from user
     * 
     * Remove a role from a user within a specific tenant context.
     * 
     * @authenticated
     */
    public function removeRole(Request $request): JsonResponse
    {
        $user = $this->userRepository->find($request->input('user_id'));

        $this->permissionService->removeRole(
            $user,
            $request->input('role'),
            $request->input('tenant_id')
        );

        return response()->json(['message' => 'Role removed successfully']);
    }

    /**
     * Assign permission to role
     * 
     * Assign a permission to a role within a specific tenant context.
     * 
     * @authenticated
     */
    public function assignPermission(AssignPermissionRequest $request): JsonResponse
    {
        $this->permissionService->assignPermissionToRole(
            $request->input('role'),
            $request->input('permission'),
            $request->input('tenant_id')
        );

        return response()->json(['message' => 'Permission assigned successfully']);
    }

    /**
     * Create role
     * 
     * Create a new role within a specific tenant context.
     * 
     * @authenticated
     */
    public function createRole(Request $request): JsonResponse
    {
        $role = $this->permissionService->createRole(
            $request->input('name'),
            $request->input('tenant_id'),
            $request->input('guard_name', 'api')
        );

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * Sync role permissions
     * 
     * Synchronize permissions for a role within a specific tenant context.
     * 
     * @authenticated
     */
    public function syncRolePermissions(Request $request, string $roleName): JsonResponse
    {
        $this->permissionService->syncRolePermissions(
            $roleName,
            $request->input('permissions', []),
            $request->input('tenant_id')
        );

        return response()->json(['message' => 'Permissions synced successfully']);
    }
}
