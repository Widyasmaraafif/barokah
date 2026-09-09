<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Rules\CityInState;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShippingRateRequest extends FormRequest
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
            'from_state' => ['nullable', 'string', Rule::in(config('malaysia.states', []))],
            'from_city' => ['nullable', 'string', 'max:100', new CityInState('from_state')],
            'to_state' => ['required', 'string', Rule::in(config('malaysia.states', []))],
            'to_city' => ['nullable', 'string', 'max:100', new CityInState('to_state')],
            'rate' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
