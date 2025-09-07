# Automatyczne Response Codes - Przykłady

Generator automatycznie dodaje standardowe response codes Laravel na podstawie kontekstu metod kontrolera, nawet jeśli nie ma jawnej dokumentacji w PHPDoc.

## 🎯 Inteligentne wykrywanie Response Codes

### Przykład 1: Podstawowy kontroler bez dokumentacji
```php
class UserController extends Controller
{
    // Automatycznie wygeneruje:
    // 200: List retrieved successfully
    // 400: Bad Request  
    // 401: Unauthorized
    // 500: Internal Server Error
    public function index(): JsonResponse
    {
        return response()->json(User::all());
    }

    // Automatycznie wygeneruje:
    // 201: Resource created successfully
    // 400: Bad Request
    // 401: Unauthorized
    // 403: Forbidden (bo tworzy resource)
    // 422: Validation error (bo ma FormRequest)
    // 500: Internal Server Error
    public function store(StoreUserRequest $request): JsonResponse
    {
        return response()->json(User::create($request->validated()), 201);
    }

    // Automatycznie wygeneruje:
    // 200: Resource retrieved successfully  
    // 400: Bad Request
    // 401: Unauthorized
    // 404: Resource not found (bo ma parametr $id)
    // 500: Internal Server Error
    public function show(int $id): JsonResponse
    {
        return response()->json(User::findOrFail($id));
    }

    // Automatycznie wygeneruje:
    // 200: Resource updated successfully
    // 400: Bad Request
    // 401: Unauthorized
    // 403: Forbidden (bo modyfikuje)
    // 404: Resource not found (bo ma $id)
    // 422: Validation error (bo ma UserData)
    // 500: Internal Server Error
    public function update(int $id, UserData $userData): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($userData->toArray());
        return response()->json($user);
    }

    // Automatycznie wygeneruje:
    // 204: Resource deleted successfully
    // 400: Bad Request
    // 401: Unauthorized  
    // 403: Forbidden (bo usuwa)
    // 404: Resource not found (bo ma $id)
    // 500: Internal Server Error
    public function destroy(int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
```

### Przykład 2: Kombinacja PHPDoc + automatyczne responses
```php
class ProductController extends Controller
{
    /**
     * Get products with filters
     * 
     * @response 200 ProductResource[] Products retrieved successfully
     * @response 429 Too many requests
     */
    public function index(ProductFilterRequest $filters): JsonResponse
    {
        // Generator doda automatycznie:
        // 200: (z PHPDoc) Products retrieved successfully  
        // 422: Validation error (bo ma ProductFilterRequest)
        // 429: (z PHPDoc) Too many requests
        // + standardowe: 400, 401, 500
        
        return ProductResource::collection(
            Product::filter($filters)->paginate()
        );
    }

    /**
     * Create product
     * 
     * @response 201 ProductResource Product created
     * @response 409 Product already exists
     */
    public function store(ProductData $data): JsonResponse
    {
        // Generator użyje:
        // 201: (z PHPDoc) Product created
        // 409: (z PHPDoc) Product already exists  
        // 422: Validation error (bo ma ProductData)
        // + standardowe: 400, 401, 403, 500
        
        return new ProductResource(Product::create($data->toArray()));
    }
}
```

## 🔧 Logika automatycznego wykrywania

### Status Codes dla Success Response:
- `store()`, `create()` → **201 Created**
- `destroy()`, `delete()` → **204 No Content**  
- Wszystkie inne → **200 OK**

### Automatyczne Error Responses:

#### Zawsze dodawane:
- **400** Bad Request
- **401** Unauthorized  
- **500** Internal Server Error

#### Na podstawie kontekstu:

**404 Not Found** - dodawane gdy:
- Metoda zawiera `show`, `update`, `destroy`
- Ma parametr `$id`, `$user_id`, `$post_id`, itp.

**422 Validation Error** - dodawane gdy:
- Ma parametr `FormRequest`
- Ma parametr `Spatie Data`
- Zawiera schema błędów walidacji:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name must be at least 3 characters."]
  }
}
```

**403 Forbidden** - dodawane gdy:
- Metoda zawiera `store`, `update`, `destroy`
- Operacje modyfikujące dane

### Przykład wygenerowanego OpenAPI:
```json
{
  "paths": {
    "/api/users/{id}": {
      "get": {
        "responses": {
          "200": {
            "description": "Resource retrieved successfully",
            "content": {
              "application/json": {
                "schema": {"$ref": "#/components/schemas/UserResource"}
              }
            }
          },
          "400": {"description": "Bad Request"},
          "401": {"description": "Unauthorized"}, 
          "404": {"description": "Resource not found"},
          "500": {"description": "Internal Server Error"}
        }
      }
    }
  }
}
```

## ⚙️ Konfiguracja

Możesz dostosować domyślne responses w `config/openapi-generator.php`:

```php
'controllers' => [
    'parse_docblocks' => true,
    'default_responses' => [
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '403' => 'Forbidden', 
        '404' => 'Not Found',
        '422' => 'Validation Error',
        '429' => 'Too Many Requests',  // Dodaj custom
        '500' => 'Internal Server Error',
    ],
],
```

## 🎨 Best Practices

1. **Kombinuj PHPDoc + automatyczne**: 
   - Używaj `@response` dla specjalnych przypadków
   - Pozwól generatorowi dodać standardowe

2. **Nazwij metody semantycznie**:
   - `index()`, `show()`, `store()`, `update()`, `destroy()`
   - Generator rozpozna kontekst automatycznie

3. **Używaj type hints**:
   - `FormRequest` → 422 Validation Error
   - `int $id` → 404 Not Found
   - `Data` objects → 422 Validation Error

4. **Dokumentuj wyjątki**:
```php
/**
 * @response 409 Email already exists
 * @response 429 Rate limit exceeded  
 */
public function store(UserData $data): JsonResponse
```

W ten sposób masz **kompletną dokumentację** bez konieczności ręcznego opisywania każdego standardowego response code! 🚀
