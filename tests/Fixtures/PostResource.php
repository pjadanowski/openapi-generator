<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'featured_image' => $this->featured_image,
            'author' => new UserResource($this->author),
            'category' => new CategoryResource($this->category),
            'tags' => $this->tags,
            'view_count' => $this->view_count,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
