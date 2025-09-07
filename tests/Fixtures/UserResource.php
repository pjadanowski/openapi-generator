<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'age' => $this->age,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'social_links' => $this->social_links,
            'roles' => $this->roles,
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'posts_count' => $this->posts_count,
            'last_login_at' => $this->last_login_at,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
