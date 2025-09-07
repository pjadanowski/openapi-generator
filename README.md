# OpenAPI Generator for Laravel Routes

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

### Publikacja konfiguracji

```bash
php artisan vendor:publish --tag=openapi-generator-config
```

## Użycie

### Podstawowe użycie

Generator automatycznie analizuje wszystkie ruty API w aplikacji:

```bash
# Generowanie dokumentacji z routów
php artisan openapi:generate

# Z niestandardowymi opcjami
php artisan openapi:generate \
    --output=public/api-docs.json \
    --title="My API" \
    --version=2.0.0 \
    --description="API documentation" \
    --format=yaml
```

### Przykład kontrolera

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

## Jak to działa

1. **Analiza routów**: Generator skanuje `php artisan route:list` dla routów API
2. **Parsowanie kontrolerów**: Analizuje metody kontrolerów i ich parametry
3. **Wykrywanie typów**:
   - **Type hints** → automatyczne mapowanie na OpenAPI typy
   - **Spatie Data** → rekurencyjna analiza właściwości DTO
   - **FormRequest** → analiza reguł walidacji
4. **Responses**: Automatyczne wykrywanie na podstawie:
   - `return new UserResource($user)` → pojedynczy obiekt
   - `UserResource::collection($users)` → tablica obiektów
   - Adnotacje PHPDoc `@response`

## 🤖 Automatyczne Response Codes

Generator inteligentnie dodaje standardowe response codes Laravel **bez konieczności ręcznego dokumentowania**:

### Automatycznie wykrywane:
- **201** dla `store()`, `create()` metod
- **204** dla `destroy()`, `delete()` metod  
- **404** gdy metoda ma parametr `$id` lub zawiera `show`, `update`, `destroy`
- **422** gdy metoda ma `FormRequest` lub `Spatie Data` 
- **403** dla operacji modyfikujących (`store`, `update`, `destroy`)
- **400, 401, 500** zawsze dodawane

### Przykład - bez dokumentacji:
```php
// Automatycznie wygeneruje responses: 201, 403, 422, 400, 401, 500
public function store(StoreUserRequest $request): JsonResponse
{
    return new UserResource(User::create($request->validated()));
}

// Automatycznie wygeneruje responses: 200, 404, 400, 401, 500  
public function show(int $id): JsonResponse
{
    return new UserResource(User::findOrFail($id));
}
```

### Kombinacja z PHPDoc:
```php
/**
 * @response 409 Email already exists
 * @response 429 Rate limit exceeded
 */
public function store(UserData $data): JsonResponse
{
    // Generator doda: 201, 409, 429, 403, 422, 400, 401, 500
}
```

👉 **Szczegóły**: Zobacz [docs/AUTOMATIC_RESPONSES.md](docs/AUTOMATIC_RESPONSES.md)

## Wyświetlanie dokumentacji

Po wygenerowaniu dokumentacji:

- **Swagger UI**: `http://your-app.com/api/documentation`
- **ReDoc**: `http://your-app.com/api/redoc`
- **Raw JSON**: `http://your-app.com/api/openapi.json`

## Konfiguracja

```php
<?php

return [
    // Informacje o API
    'info' => [
        'title' => env('APP_NAME', 'Laravel') . ' API Documentation',
        'version' => '1.0.0',
        'description' => 'API documentation generated from routes',
    ],

    // Konfiguracja wyjścia
    'output' => [
        'path' => 'public/openapi.json',
        'format' => 'json',
    ],

    // Filtry routów
    'routes' => [
        'include_patterns' => ['api/*'],
        'exclude_patterns' => ['api/internal/*'],
    ],

    // Konfiguracja Swagger UI
    'swagger' => [
        'enabled' => true,
        'route' => '/api/documentation',
        'middleware' => ['web'],
    ],
];
```

## Obsługiwane funkcje

### ✅ Analiza routów
- Automatyczne wykrywanie routów API
- Parametry ścieżki (`{id}`, `{slug?}`)
- Metody HTTP (GET, POST, PUT, PATCH, DELETE)

### ✅ Kontrolery
- Dokumentacja PHPDoc
- Adnotacje `@response` z kodami statusu
- Automatyczne wykrywanie description

### ✅ Parametry wejściowe
- **Spatie Data** → pełna analiza typu
- **FormRequest** → reguły walidacji → OpenAPI schema
- **Type hints** → podstawowe typy PHP

### ✅ Responses
- **JsonResource** → analiza `toArray()` 
- **ResourceCollection** → automatyczna detekcja tablic
- **PHPDoc annotations** → `@response 200 UserResource`

### ✅ Obsługiwane typy
- Podstawowe: `string`, `int`, `float`, `bool`, `array`
- Nullable: `?string`, `string|null`
- Union types: `string|int`
- Collections: `Collection<UserData>`
- Nested Data objects

## Wymagania

- PHP 8.1+
- Laravel 9.0+
- OpenAPI 3.1+

## Licencja

MIT License
