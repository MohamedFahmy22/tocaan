<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing order.
 */
class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],

            // Items are optional for updates
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.min' => 'If updating items, at least one item is required.',
            'items.*.product_name.required_with' => 'Product name is required for each item.',
            'items.*.product_name.max' => 'Product name cannot exceed 255 characters.',
            'items.*.quantity.required_with' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Quantity cannot exceed 9999.',
            'items.*.unit_price.required_with' => 'Unit price is required for each item.',
            'items.*.unit_price.min' => 'Unit price must be at least 0.01.',
            'items.*.unit_price.max' => 'Unit price cannot exceed 999999.99.',
        ];
    }
}
