<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Fixtures;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        #[Required]
        public string $email,
        public ?int $age = null,
        public array $roles = [],
    ) {}
}
