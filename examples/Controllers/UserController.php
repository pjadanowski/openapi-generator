<?php

namespace App\Http\Controllers\Api;

use App\Data\UserData;
use App\Data\UserFilterData;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * User management endpoints
 */
class UserController extends Controller
{
    /**
     * Get paginated list of users
     * 
     * This endpoint returns a paginated list of all users in the system.
     * You can filter results using query parameters.
     * 
     * @response 200 UserResource[]
     */
    public function index(UserFilterData $filters): JsonResponse
    {
        $query = User::query();
        
        if ($filters->name) {
            $query->where('name', 'like', "%{$filters->name}%");
        }
        
        if ($filters->email) {
            $query->where('email', 'like', "%{$filters->email}%");
        }
        
        $users = $query->paginate($filters->per_page ?? 15);
        
        return UserResource::collection($users);
    }

    /**
     * Create a new user
     * 
     * Creates a new user account with the provided information.
     * 
     * @response 201 UserResource User created successfully
     * @response 422 Validation error
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        
        return response()->json(
            new UserResource($user),
            201
        );
    }

    /**
     * Get user by ID
     * 
     * Retrieve detailed information about a specific user.
     * 
     * @response 200 UserResource User found
     * @response 404 User not found
     */
    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        return response()->json(new UserResource($user));
    }

    /**
     * Update user with Spatie Data
     * 
     * Updates user information using structured data validation.
     * 
     * @response 200 UserResource User updated successfully
     * @response 404 User not found
     * @response 422 Validation error
     */
    public function update(int $id, UserData $userData): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($userData->toArray());
        
        return response()->json(new UserResource($user));
    }

    /**
     * Update user with FormRequest
     * 
     * Alternative update method using traditional FormRequest validation.
     */
    public function updateWithRequest(int $id, UpdateUserRequest $request): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());
        
        return response()->json(new UserResource($user));
    }

    /**
     * Delete user
     * 
     * Permanently removes a user from the system.
     * 
     * @response 204 User deleted successfully
     * @response 404 User not found
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return response()->json(null, 204);
    }

    /**
     * Get user statistics
     * 
     * @response 200 {
     *   "total_users": 150,
     *   "active_users": 120,
     *   "new_users_today": 5
     * }
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
        ]);
    }
}
