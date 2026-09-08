<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\ProfileValidationRules;
use App\Rules\CityInState;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->profileRules($this->user()?->id), [
            // Phone/postcode formats are TBC (spec §24 item 5); only length is enforced.
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', Rule::in(config('malaysia.states', []))],
            'city' => ['nullable', 'string', 'max:100', new CityInState('state')],
            'post_code' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
