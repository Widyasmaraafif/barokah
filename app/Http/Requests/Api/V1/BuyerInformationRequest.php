<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Direct Buy order payload (spec §11.4/§14.4).
 *
 * Guest checkout is allowed; the five buyer fields are required exactly
 * per spec §3/§6.1. Email stays optional (spec §24 item 4). Phone/postcode
 * formats are TBC (spec §24 item 5), so only length is enforced.
 * Single-product Buy uses product_id/quantity; items[] allows one
 * checkout with products from multiple sellers (spec §14.3).
 */
class BuyerInformationRequest extends FormRequest
{
    /**
     * Guest buyers may check out without authentication (spec §4.3).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required_without:items', 'nullable', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required_without:items', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'items' => ['required_without:product_id', 'nullable', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'buyer' => ['required', 'array'],
            'buyer.name' => ['required', 'string', 'max:255'],
            'buyer.address' => ['required', 'string', 'max:500'],
            'buyer.state' => ['required', 'string', 'max:100'],
            'buyer.post_code' => ['required', 'string', 'max:20'],
            'buyer.phone' => ['required', 'string', 'max:30'],
            'buyer.email' => ['nullable', 'email', 'max:255'],
            // Fixed Rate is the default; external resolves via ShippingService (spec §16).
            'shipping_method' => ['nullable', 'string', Rule::in(['fixed', 'external'])],
        ];
    }
}
