<?php

namespace Pjadanowski\OpenApiGenerator\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\StoreUserRequest;

class TestController extends Controller
{
    /**
     * Get all users
     */
    public function index(): JsonResponse
    {
        // Should automatically get: 200, 400, 401, 500
        return response()->json(['users' => []]);
    }

    /**
     * Display user
     */
    public function show(int $id): JsonResponse
    {
        // Should automatically get: 200, 404 (has $id), 400, 401, 500
        return response()->json(['user' => ['id' => $id, 'name' => 'Test']]);
    }

    /**
     * Store new user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Should automatically get: 201 (store method), 422 (FormRequest), 403 (store method), 400, 401, 500
        return response()->json(['user' => ['id' => 1, 'name' => 'Test']], 201);
    }

    /**
     * Update user
     */
    public function update(int $id, StoreUserRequest $request): JsonResponse
    {
        // Should automatically get: 200, 404 (has $id + update method), 422 (FormRequest), 403 (update method), 400, 401, 500
        return response()->json(['user' => ['id' => $id, 'name' => 'Updated']]);
    }

    /**
     * Delete user
     */
    public function destroy(int $id): JsonResponse
    {
        // Should automatically get: 204 (destroy method), 404 (has $id + destroy method), 403 (destroy method), 400, 401, 500
        return response()->json(null, 204);
    }

    /**
     * Get user posts
     */
    public function userPosts(int $userId): JsonResponse
    {
        // Should automatically get: 200, 404 (findOrFail detection), 400, 401, 500
        // Using findOrFail pattern for detection
        $user = $this->findOrFail($userId);
        return response()->json(['posts' => []]);
    }

    /**
     * Simple action without specific patterns
     */
    public function customAction(): JsonResponse
    {
        // Should automatically get: 200, 400, 401, 500
        return response()->json(['status' => 'ok']);
    }
}
