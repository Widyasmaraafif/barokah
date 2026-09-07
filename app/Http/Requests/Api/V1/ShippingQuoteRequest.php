<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shipping quote payload (spec §11.6/§16.2).
 *
 * Guest quoting is allowed; address fields are required. Either a direct
 * subtotal or product lines may be supplied to evaluate the free-shipping
 * threshold (TBC default until confirmed, spec §24 item 17).
 * Phone/postcode formats are TBC (spec §24 item 5); only length enforced.
 */
class ShippingQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:500'],
            'state' => ['required', 'string', 'max:100'],
            'post_code' => ['required', 'string', 'max:20'],
            'method' => ['nullable', 'string', Rule::in(['fixed', 'external'])],
            'subtotal' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items' => ['nullable', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required_with:items', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
