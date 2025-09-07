<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Fixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TestController extends Controller
{
    // Single Resource Tests
    public function singleUser(): UserResource
    {
        $user = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'age' => 30
        ];
        return new UserResource($user);
    }

    public function singlePost(): PostResource
    {
        $post = (object) [
            'id' => 1,
            'title' => 'Test Post',
            'content' => 'Test content',
            'author' => (object) ['id' => 1, 'name' => 'John'],
            'category' => (object) ['id' => 1, 'name' => 'Tech']
        ];
        return new PostResource($post);
    }

    public function singleCategory(): CategoryResource
    {
        $category = (object) [
            'id' => 1,
            'name' => 'Technology',
            'slug' => 'technology',
            'color' => '#007bff'
        ];
        return new CategoryResource($category);
    }

    // Collection Resource Tests
    public function userCollection(): AnonymousResourceCollection
    {
        $users = collect([
            (object) ['id' => 1, 'name' => 'John'],
            (object) ['id' => 2, 'name' => 'Jane']
        ]);
        return UserResource::collection($users);
    }

    public function postCollection(): AnonymousResourceCollection
    {
        $posts = collect([
            (object) ['id' => 1, 'title' => 'Post 1'],
            (object) ['id' => 2, 'title' => 'Post 2']
        ]);
        return PostResource::collection($posts);
    }

    public function categoryCollection(): AnonymousResourceCollection
    {
        $categories = collect([
            (object) ['id' => 1, 'name' => 'Tech'],
            (object) ['id' => 2, 'name' => 'News']
        ]);
        return CategoryResource::collection($categories);
    }

    // Nested Resource Tests
    public function userWithPosts(): UserResource
    {
        $user = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'posts' => collect([
                (object) ['id' => 1, 'title' => 'Post 1'],
                (object) ['id' => 2, 'title' => 'Post 2']
            ])
        ];
        return new UserResource($user);
    }

    public function postWithAuthorAndCategory(): PostResource
    {
        $post = (object) [
            'id' => 1,
            'title' => 'Complex Post',
            'content' => 'Content with relations',
            'author' => (object) [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'category' => (object) [
                'id' => 1,
                'name' => 'Technology',
                'slug' => 'tech',
                'color' => '#007bff'
            ]
        ];
        return new PostResource($post);
    }

    // JSON Response Tests
    public function rawJsonResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Success',
            'data' => [
                'total' => 100,
                'processed' => 50
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0'
            ]
        ]);
    }

    public function errorResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'Not Found',
            'message' => 'Resource not found',
            'code' => 404
        ], 404);
    }

    // Mixed Response Tests
    public function mixedContentResponse(): JsonResponse
    {
        return response()->json([
            'users' => UserResource::collection(collect()),
            'posts' => PostResource::collection(collect()),
            'categories' => CategoryResource::collection(collect()),
            'stats' => [
                'total_users' => 150,
                'total_posts' => 75,
                'total_categories' => 10
            ]
        ]);
    }
}
