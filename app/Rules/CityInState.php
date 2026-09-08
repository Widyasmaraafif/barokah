<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CityInState implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(protected string $stateField = 'state') {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $state = data_get($this->data, $this->stateField);

        if (! is_string($state) || $state === '') {
            $fail('Select a state before choosing a city.');

            return;
        }

        /** @var array<string, array<int, string>> $cities */
        $cities = config('malaysia_cities', []);

        if (! array_key_exists($state, $cities)) {
            return;
        }

        if (! in_array($value, $cities[$state], true)) {
            $fail('The selected city does not belong to the selected state.');
        }
    }
}
