<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Max;

/**
 * User data transfer object
 */
class UserData extends Data
{
    public function __construct(
        #[Required, Min(2), Max(100)]
        public string $name,
        
        #[Required, Email]
        public string $email,
        
        #[Min(18), Max(120)]
        public ?int $age = null,
        
        public ?string $phone = null,
        public bool $is_active = true,
        public array $roles = [],
        
        public ?AddressData $address = null,
    ) {}
}

/**
 * User filtering options
 */
class UserFilterData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?bool $is_active = null,
        public ?int $min_age = null,
        public ?int $max_age = null,
        public int $per_page = 15,
        public int $page = 1,
        public array $roles = [],
    ) {}
}

/**
 * Address information
 */
class AddressData extends Data
{
    public function __construct(
        #[Required]
        public string $street,
        
        #[Required]
        public string $city,
        
        #[Required]
        public string $postal_code,
        
        #[Required]
        public string $country,
        
        public ?string $state = null,
    ) {}
}

/**
 * Product data
 */
class ProductData extends Data
{
    public function __construct(
        #[Required, Min(3), Max(200)]
        public string $name,
        
        #[Required]
        public string $description,
        
        #[Required, Min(0)]
        public float $price,
        
        public string $sku = '',
        public bool $is_active = true,
        public int $stock_quantity = 0,
        
        public ?CategoryData $category = null,
        
        /** @var array<string> */
        public array $tags = [],
        
        /** @var array<ImageData> */
        public array $images = [],
    ) {}
}

/**
 * Category data
 */
class CategoryData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        
        #[Required]
        public string $slug,
        
        public ?string $description = null,
        public ?CategoryData $parent = null,
        public bool $is_active = true,
    ) {}
}

/**
 * Image data
 */
class ImageData extends Data
{
    public function __construct(
        #[Required]
        public string $url,
        
        #[Required]
        public string $alt_text,
        
        public int $width = 0,
        public int $height = 0,
        public string $mime_type = 'image/jpeg',
    ) {}
}
