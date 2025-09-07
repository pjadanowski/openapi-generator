# OpenAPI Generator for Laravel Routes

A PHP/Composer package for generating OpenAPI 3.1+ documentation based on analysis of Laravel routes, controllers, Spatie Data, and FormRequest.

## Features

- ⚡️ **Automatic route analysis** Laravel (`php artisan route:list`)
- 🎯 **Controller parsing** and their methods- PHP 8.1+
- Laravel 9.0+
- OpenAPI 3.1+

## License

MIT LicenseHPDoc documentation
- 📝 **Spatie Data support** as DTOs with type hints
- ✅ **FormRequest analysis** for parameter validation
- 🔄 **Automatic response detection** from Resources
- 📊 **OpenAPI 3.0+ generation** JSON/YAML
- 🎨 **Built-in Swagger UI and ReDoc support**
- ⚡ **Artisan command** for quick generation

## Installationtor for Laravel Routes

Paczka PHP/Composer do generowania dokumentacji OpenAPI 3.1+ na podstawie analizy routów Laravel, kontrolerów, Spatie Data i FormRequest.

## Funkcje

- �️ **Automatyczna analiza routów** Laravel (`php artisan route:list`)
- 🎯 **Parsowanie kontrolerów** i ich metod z dokumentacją PHPDoc
- 📝 **Obsługa Spatie Data** jako DTO z type hintami
- ✅ **Analiza FormRequest** dla walidacji parametrów
- 🔄 **Automatyczne wykrywanie responses** z Resource'ów
- 📊 **Generowanie OpenAPI 3.0+** JSON/YAML
- 🎨 **Wbudowana obsługa Swagger UI i ReDoc**
- ⚡ **Komenda Artisan** do szybkiego generowania

## Instalacja

```bash
composer require pjadanowski/openapi-generator
```

### Publishing configuration

```bash
php artisan vendor:publish --tag=openapi-generator-config
```

## Usage

### Basic usage

The generator automatically analyzes all API routes in the application:

```bash
# Generate documentation from routes
php artisan openapi:generate

# With custom options
php artisan openapi:generate \
    --output=public/api-docs.json \
    --title="My API" \
    --version=2.0.0 \
    --description="API documentation" \
    --format=yaml
```

### Controller example

```php
<?php

namespace App\Http\Controllers\Api;

use App\Data\UserData;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get all users
     * 
     * @response 200 UserResource[]
     */
    public function index(): JsonResponse
    {
        $users = User::all();
        return UserResource::collection($users);
    }

    /**
     * Create a new user
     * 
     * @response 201 UserResource
     * @response 422 Validation error
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        return new UserResource($user);
    }

    /**
     * Update user with Data DTO
     */
    public function update(int $id, UserData $userData): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($userData->toArray());
        return new UserResource($user);
    }
}
```

### Spatie Data DTO

```php
<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        
        #[Required, Email]
        public string $email,
        
        public ?int $age = null,
        public array $roles = [],
    ) {}
}
```

### FormRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'age' => 'nullable|integer|min:18|max:120',
            'roles' => 'array',
            'roles.*' => 'string|in:admin,user,moderator',
        ];
    }
}
```

### Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'age' => $this->age,
            'roles' => $this->roles,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## How it works

1. **Route analysis**: Generator scans `php artisan route:list` for API routes
2. **Controller parsing**: Analyzes controller methods and their parameters
3. **Type detection**:
   - **Type hints** → automatic mapping to OpenAPI types
   - **Spatie Data** → recursive analysis of DTO properties
   - **FormRequest** → validation rules analysis
4. **Responses**: Automatic detection based on:
   - `return new UserResource($user)` → single object
   - `UserResource::collection($users)` → array of objects
   - PHPDoc annotations `@response`

## 🤖 Automatic Response Codes

The generator intelligently adds standard Laravel response codes **without requiring manual documentation**:

### Automatically detected:
- **201** for `store()`, `create()` methods
- **204** for `destroy()`, `delete()` methods  
- **404** when method has `$id` parameter or contains `show`, `update`, `destroy`
- **422** when method has `FormRequest` or `Spatie Data` 
- **403** for modifying operations (`store`, `update`, `destroy`)
- **400, 401, 500** always added

### Example - without documentation:
```php
// Automatically generates responses: 201, 403, 422, 400, 401, 500
public function store(StoreUserRequest $request): JsonResponse
{
    return new UserResource(User::create($request->validated()));
}

// Automatically generates responses: 200, 404, 400, 401, 500  
public function show(int $id): JsonResponse
{
    return new UserResource(User::findOrFail($id));
}
```

### Combined with PHPDoc:
```php
/**
 * @response 409 Email already exists
 * @response 429 Rate limit exceeded
 */
public function store(UserData $data): JsonResponse
{
    // Generator will add: 201, 409, 429, 403, 422, 400, 401, 500
}
```

👉 **Details**: See [docs/AUTOMATIC_RESPONSES.md](docs/AUTOMATIC_RESPONSES.md)

## Displaying documentation

After generating documentation:

- **Swagger UI**: `http://your-app.com/api/docs`
- **ReDoc**: `http://your-app.com/api/redoc`
- **Raw JSON**: `http://your-app.com/api/openapi.json`

## Configuration

```php
<?php

return [
    // API information
    'info' => [
        'title' => env('APP_NAME', 'Laravel') . ' API Documentation',
        'version' => '1.0.0',
        'description' => 'API documentation generated from routes',
    ],

    // Output configuration
    'output' => [
        'path' => 'public/openapi.json',
        'format' => 'json',
    ],

    // Route filters
    'routes' => [
        'include_patterns' => ['api/*'],
        'exclude_patterns' => ['api/internal/*'],
    ],

    // Swagger UI configuration
    'swagger' => [
        'enabled' => true,
        'route' => '/api/docs',
        'middleware' => ['web'],
    ],
];
```

## Supported features

### ✅ Route analysis
- Automatic API route detection
- Path parameters (`{id}`, `{slug?}`)
- HTTP methods (GET, POST, PUT, PATCH, DELETE)

### ✅ Controllers
- PHPDoc documentation
- `@response` annotations with status codes
- Automatic description detection

### ✅ Input parameters
- **Spatie Data** → full type analysis
- **FormRequest** → validation rules → OpenAPI schema
- **Type hints** → basic PHP types

### ✅ Responses
- **JsonResource** → `toArray()` analysis 
- **ResourceCollection** → automatic array detection
- **PHPDoc annotations** → `@response 200 UserResource`

### ✅ Supported types
- Basic: `string`, `int`, `float`, `bool`, `array`
- Nullable: `?string`, `string|null`
- Union types: `string|int`
- Collections: `Collection<UserData>`
- Nested Data objects

## Requirements

- PHP 8.1+
- Laravel 9.0+
- OpenAPI 3.0+

## Licencja

MIT License
