# Instrukcje instalacji i użytkowania

## Wymagania wstępne

- PHP 8.1 lub nowszy
- Laravel 9.0 lub nowszy
- Composer

## Krok po kroku

### 1. Instalacja paczki

```bash
composer require pjadanowski/openapi-generator
```

### 2. Publikacja konfiguracji

```bash
php artisan vendor:publish --tag=openapi-generator-config
```

### 3. Edycja konfiguracji

Edytuj plik `config/openapi-generator.php` aby dostosować ustawienia:

```php
<?php

return [
    'info' => [
        'title' => 'Moja aplikacja API',
        'version' => '1.0.0',
        'description' => 'Dokumentacja API wygenerowana automatycznie',
    ],
    
    'output' => [
        'path' => 'public/openapi.json',
        'format' => 'json', // lub 'yaml'
    ],
    
    'routes' => [
        'include_patterns' => ['api/*'],
        'exclude_patterns' => ['api/internal/*'],
    ],
];
```

### 4. Tworzenie kontrolerów API

Utwórz kontrolery w katalogu `app/Http/Controllers/Api/`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Data\UserData;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

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
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        return new UserResource($user);
    }
}
```

### 5. Tworzenie klas Data (Spatie)

Utwórz klasy Data w katalogu `app/Data/`:

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
    ) {}
}
```

### 6. Tworzenie FormRequest

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
            'age' => 'nullable|integer|min:18',
        ];
    }
}
```

### 7. Tworzenie Resource

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
            'created_at' => $this->created_at,
        ];
    }
}
```

### 8. Rejestracja routów API

W pliku `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);
```

### 9. Generowanie dokumentacji

```bash
php artisan openapi:generate
```

### 10. Przeglądanie dokumentacji

Otwórz w przeglądarce:
- Swagger UI: `http://localhost/api/documentation`
- ReDoc: `http://localhost/api/redoc`
- Raw JSON: `http://localhost/api/openapi.json`

## Opcje zaawansowane

### Generowanie z niestandardowymi parametrami

```bash
php artisan openapi:generate \
    --output=public/custom-api.json \
    --title="Custom API" \
    --version=2.0.0 \
    --format=yaml
```

### Filtrowanie routów

W konfiguracji możesz określić które ruty mają być analizowane:

```php
'routes' => [
    'include_patterns' => [
        'api/v1/*',
        'api/v2/users/*',
    ],
    'exclude_patterns' => [
        'api/internal/*',
        'api/debug/*',
        'api/*/health',
    ],
],
```

### Dokumentowanie responses w PHPDoc

```php
/**
 * Get user by ID
 * 
 * @response 200 UserResource User found successfully
 * @response 404 User not found
 * @response 422 {
 *   "message": "Validation failed",
 *   "errors": {
 *     "email": ["Email already exists"]
 *   }
 * }
 */
public function show(int $id): JsonResponse
{
    // ...
}
```

### Nested Data Objects

```php
class UserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?AddressData $address = null,
        
        /** @var array<RoleData> */
        public array $roles = [],
    ) {}
}

class AddressData extends Data
{
    public function __construct(
        public string $street,
        public string $city,
        public string $country,
    ) {}
}
```

### Programowe użycie

```php
use Pjadanowski\OpenApiGenerator\RouteBasedOpenApiGenerator;

$generator = app(RouteBasedOpenApiGenerator::class);
$openApi = $generator->generateFromRoutes([
    'title' => 'My API',
    'version' => '1.0.0',
]);

// Zapisz do pliku
file_put_contents('openapi.json', \cebe\openapi\Writer::writeToJson($openApi));
```

## Rozwiązywanie problemów

### Problem: "No routes found"

Upewnij się, że:
1. Ruty są zarejestrowane w `routes/api.php`
2. Ruty mają prefix `api/` lub są w middleware `api`
3. Konfiguracja `include_patterns` jest poprawna

### Problem: "Controller not found"

Sprawdź:
1. Czy kontrolery mają poprawne namespace
2. Czy klasy kontrolerów istnieją
3. Czy metody kontrolerów są publiczne

### Problem: Błędne typy w dokumentacji

1. Użyj type hints PHP 8.1+
2. Dodaj atrybuty `#[Required]` w Spatie Data
3. Użyj komentarzy PHPDoc dla kolekcji
4. Sprawdź reguły walidacji w FormRequest

### Problem: Missing schemas

1. Upewnij się, że klasy Data rozszerzają `Spatie\LaravelData\Data`
2. Sprawdź czy klasy są autoloadowane
3. Verify FormRequest rules syntax
