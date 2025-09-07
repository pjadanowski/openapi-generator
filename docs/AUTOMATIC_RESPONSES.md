# Automatic Response Codes - Examples

The generator automatically adds standard Laravel response codes based on the context of controller methods, even if there's no explicit documentation in PHPDoc.

## 🎯 Intelligent Response Code Detection

### Example 1: Basic controller without documentation
```php
class UserController extends Controller
{
    // Automatically generates:
    // 200: List retrieved successfully
    // 400: Bad Request  
    // 401: Unauthorized
    // 500: Internal Server Error
    public function index(): JsonResponse
    {
        return response()->json(User::all());
    }

    // Automatically generates:
    // 201: Resource created successfully
    // 400: Bad Request
    // 401: Unauthorized
    // 403: Forbidden (because it creates resource)
    // 422: Validation error (because it has FormRequest)
    // 500: Internal Server Error
    public function store(StoreUserRequest $request): JsonResponse
    {
        return response()->json(User::create($request->validated()), 201);
    }

    // Automatically generates:
    // 200: Resource retrieved successfully  
    // 400: Bad Request
    // 401: Unauthorized
    // 404: Resource not found (because it has $id parameter)
    // 500: Internal Server Error
    public function show(int $id): JsonResponse
    {
        return response()->json(User::findOrFail($id));
    }

    // Automatically generates:
    // 200: Resource updated successfully
    // 400: Bad Request
    // 401: Unauthorized
    // 403: Forbidden (because it modifies)
    // 404: Resource not found (because it has $id)
    // 422: Validation error (because it has UserData)
    // 500: Internal Server Error
    public function update(int $id, UserData $userData): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($userData->toArray());
        return response()->json($user);
    }

    // Automatically generates:
    // 204: Resource deleted successfully
    // 400: Bad Request
    // 401: Unauthorized  
    // 403: Forbidden (because it deletes)
    // 404: Resource not found (because it has $id)
    // 500: Internal Server Error
    public function destroy(int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
```

### Example 2: Combination of PHPDoc + automatic responses
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
        // Generator will automatically add:
        // 200: (from PHPDoc) Products retrieved successfully  
        // 422: Validation error (because it has ProductFilterRequest)
        // 429: (from PHPDoc) Too many requests
        // + standard: 400, 401, 500
        
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
        // Generator will use:
        // 201: (from PHPDoc) Product created
        // 409: (from PHPDoc) Product already exists  
        // 422: Validation error (because it has ProductData)
        // + standard: 400, 401, 403, 500
        
        return new ProductResource(Product::create($data->toArray()));
    }
}
```

## 🔧 Automatic detection logic

### Status Codes for Success Response:
- `store()`, `create()` → **201 Created**
- `destroy()`, `delete()` → **204 No Content**  
- All others → **200 OK**

### Automatic Error Responses:

#### Always added:
- **400** Bad Request
- **401** Unauthorized  
- **500** Internal Server Error

#### Based on context:

**404 Not Found** - added when:
- Method contains `show`, `update`, `destroy`
- Has parameter `$id`, `$user_id`, `$post_id`, etc.

**422 Validation Error** - added when:
- Has `FormRequest` parameter
- Has `Spatie Data` parameter
- Contains validation error schema:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name must be at least 3 characters."]
  }
}
```

**403 Forbidden** - added when:
- Method contains `store`, `update`, `destroy`
- Data modification operations

### Example of generated OpenAPI:
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

## ⚙️ Configuration

You can customize default responses in `config/openapi-generator.php`:

```php
'controllers' => [
    'parse_docblocks' => true,
    'default_responses' => [
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '403' => 'Forbidden', 
        '404' => 'Not Found',
        '422' => 'Validation Error',
        '429' => 'Too Many Requests',  // Add custom
        '500' => 'Internal Server Error',
    ],
],
```

## 🎨 Best Practices

1. **Combine PHPDoc + automatic**: 
   - Use `@response` for special cases
   - Let the generator add standard ones

2. **Name methods semantically**:
   - `index()`, `show()`, `store()`, `update()`, `destroy()`
   - Generator will recognize context automatically

3. **Use type hints**:
   - `FormRequest` → 422 Validation Error
   - `int $id` → 404 Not Found
   - `Data` objects → 422 Validation Error

4. **Document exceptions**:
```php
/**
 * @response 409 Email already exists
 * @response 429 Rate limit exceeded  
 */
public function store(UserData $data): JsonResponse
```

This way you have **complete documentation** without the need to manually describe every standard response code! 🚀
