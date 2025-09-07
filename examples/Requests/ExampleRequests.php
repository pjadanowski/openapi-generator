<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store user request validation
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'age' => 'nullable|integer|min:18|max:120',
            'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'string|in:admin,user,moderator,editor',
            'address' => 'nullable|array',
            'address.street' => 'required_with:address|string|max:255',
            'address.city' => 'required_with:address|string|max:100',
            'address.postal_code' => 'required_with:address|string|max:20',
            'address.country' => 'required_with:address|string|size:2',
            'address.state' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'roles.*.in' => 'Invalid role selected.',
        ];
    }
}

/**
 * Update user request validation
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');
        
        return [
            'name' => 'sometimes|required|string|min:2|max:100',
            'email' => "sometimes|required|email|unique:users,email,{$userId}",
            'password' => 'sometimes|string|min:8|confirmed',
            'age' => 'nullable|integer|min:18|max:120',
            'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'string|in:admin,user,moderator,editor',
        ];
    }
}

/**
 * Product filter request
 */
class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'sort_by' => 'nullable|string|in:name,price,created_at,updated_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
