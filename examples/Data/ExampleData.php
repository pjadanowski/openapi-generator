<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\DataCollection;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required, Email]
        public string $email,

        public ?int $age = null,
        public ?string $avatar_url = null,
        public array $roles = [],

        /** @var DataCollection<RoleData> */
        public ?DataCollection $permissions = null,
    ) {}
}

class RoleData extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required]
        public string $slug,

        public ?string $description = null,
    ) {}
}

class ProductData extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required]
        public float $price,

        public ?string $description = null,
        public bool $is_active = true,
        public array $tags = [],

        public ?CategoryData $category = null,
    ) {}
}

class CategoryData extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required]
        public string $slug,

        public ?string $description = null,
        public ?CategoryData $parent = null,
    ) {}
}
