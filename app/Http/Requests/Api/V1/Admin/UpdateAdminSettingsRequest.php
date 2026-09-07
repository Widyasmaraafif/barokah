<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SettingType;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Batch (`settings: [{key, value}]`) or single (`key` + `value`) admin
 * setting update (spec §11.8).
 *
 * Array-of-objects payload avoids Laravel's dot-notation parsing because
 * setting keys are dotted (`branding.site_name`). Unknown keys are
 * rejected; private keys sent back with the masked sentinel are skipped
 * so secrets are never overwritten with the mask.
 */
class UpdateAdminSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['sometimes', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'key' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }

    public function withValidator(ValidatorInstance $validator): void
    {
        $validator->after(function (ValidatorInstance $validator): void {
            $pairs = $this->pairs();

            if ($pairs === []) {
                $validator->errors()->add('settings', 'Provide a settings batch or a single key/value pair.');

                return;
            }

            $known = $this->knownKeys();

            foreach ($pairs as $key => $value) {
                if (! in_array($key, $known, true)) {
                    $validator->errors()->add('settings.'.$key, "Unknown setting key: {$key}.");

                    continue;
                }

                if ($this->isUnchangedPrivate($key, $value)) {
                    continue;
                }

                $inner = Validator::make(['value' => $value], ['value' => $this->rulesForKey($key)]);

                if ($inner->fails()) {
                    foreach ($inner->errors()->get('value') as $message) {
                        $validator->errors()->add('settings.'.$key, $message);
                    }
                }
            }
        });
    }

    /**
     * Normalized setting key/value pairs from batch or single payload.
     *
     * @return array<string, mixed>
     */
    public function pairs(): array
    {
        $pairs = [];

        $batch = $this->input('settings', []);

        if (is_array($batch)) {
            foreach ($batch as $entry) {
                if (is_array($entry) && isset($entry['key'])) {
                    $pairs[(string) $entry['key']] = $entry['value'] ?? null;
                }
            }
        }

        if ($this->has('key')) {
            $pairs[(string) $this->input('key')] = $this->input('value');
        }

        return $pairs;
    }

    /**
     * Known setting keys from config defaults plus database rows.
     *
     * @return array<int, string>
     */
    public function knownKeys(): array
    {
        $configKeys = array_keys(config('marketplace.settings_defaults', []));
        $dbKeys = Setting::query()->pluck('key')->all();

        return array_values(array_unique([...$configKeys, ...$dbKeys]));
    }

    public function isUnchangedPrivate(string $key, mixed $value): bool
    {
        if ($value !== SettingResource::MASKED_SENTINEL) {
            return false;
        }

        return ! $this->isPublicKey($key);
    }

    public function isPublicKey(string $key): bool
    {
        $row = Setting::query()->where('key', $key)->first();
        $definition = config('marketplace.settings_defaults.'.$key, []);

        return (bool) ($row?->is_public ?? $definition['is_public'] ?? false);
    }

    /**
     * Strip tags and trim string-like values (spec §20 sanitization).
     */
    public function sanitizedValue(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $this->typeForKey($key);

        if (in_array($type, [SettingType::String, SettingType::Color, SettingType::Image], true) && is_string($value)) {
            return trim(strip_tags($value));
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => is_string($item) ? trim(strip_tags($item)) : $item, $value);
        }

        return $value;
    }

    /**
     * Per-key validation rules (spec §11.8); falls back to type rules.
     *
     * @return array<int, mixed>
     */
    public function rulesForKey(string $key): array
    {
        return match ($key) {
            'general.site_tagline' => ['nullable', 'string', 'max:255'],
            'general.maintenance_mode', 'general.allow_registration' => ['boolean'],
            'branding.site_name' => ['nullable', 'string', 'max:255'],
            'branding.logo_url', 'branding.favicon_url', 'seo.og_image' => ['nullable', 'string', 'max:2000'],
            'branding.primary_color', 'branding.primary_hover_color',
            'branding.primary_soft_color', 'branding.secondary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'currency.code' => ['required', 'string', 'max:10'],
            'currency.symbol' => ['required', 'string', 'max:10'],
            'currency.decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'currency.thousands_separator', 'currency.decimal_separator' => ['required', 'string', 'max:5'],
            'marketplace.name' => ['required', 'string', 'max:255'],
            'marketplace.description' => ['nullable', 'string', 'max:2000'],
            'marketplace.status' => ['required', 'string', 'in:open,closed,maintenance'],
            'marketplace.seller_registration_enabled', 'marketplace.reviews_enabled',
            'marketplace.inventory_tracking_enabled' => ['boolean'],
            'checkout.order_expiration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'checkout.min_order_amount', 'checkout.max_order_amount' => ['nullable', 'string', 'max:20', 'regex:/^\d+(\.\d{1,2})?$/'],
            'checkout.guest_checkout_enabled' => ['boolean'],
            'payment.fpx_enabled', 'payment.duitnow_enabled',
            'payment.sandbox_enabled' => ['boolean'],
            'payment.gateway' => ['required', 'string', 'max:50'],
            'payment.merchant_id', 'payment.secret_key',
            'payment.api_base' => ['nullable', 'string', 'max:2000'],
            'shipping.method' => ['required', 'string', 'in:fixed,external'],
            'shipping.fixed_rate', 'shipping.free_shipping_threshold' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d{1,2})?$/'],
            'shipping.free_shipping_enabled', 'shipping.api_enabled' => ['boolean'],
            'shipping.provider_name' => ['nullable', 'string', 'max:255'],
            'shipping.api_key', 'shipping.api_secret',
            'shipping.api_base_url' => ['nullable', 'string', 'max:2000'],
            'localization.default_language' => ['required', 'string', 'in:en,ms'],
            'localization.available_languages' => ['required', 'array', 'min:1'],
            'localization.available_languages.*' => ['string', 'in:en,ms'],
            'localization.timezone' => ['required', 'timezone:all'],
            'localization.date_format', 'localization.time_format' => ['required', 'string', 'max:20'],
            'localization.gtranslate_enabled' => ['boolean'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone', 'contact.business_hours',
            'contact.whatsapp' => ['nullable', 'string', 'max:100'],
            'contact.address' => ['nullable', 'string', 'max:1000'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description', 'seo.keywords' => ['nullable', 'string', 'max:500'],
            'email.from_name' => ['nullable', 'string', 'max:255'],
            'email.from_address' => ['nullable', 'email', 'max:255'],
            'email.smtp_host', 'email.smtp_username' => ['nullable', 'string', 'max:255'],
            'email.smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'email.smtp_password' => ['nullable', 'string', 'max:2000'],
            'email.smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,starttls'],
            default => $this->rulesForType($this->typeForKey($key)),
        };
    }

    /**
     * @return array<int, mixed>
     */
    protected function rulesForType(SettingType $type): array
    {
        return match ($type) {
            SettingType::Integer => ['required', 'integer'],
            SettingType::Boolean => ['boolean'],
            SettingType::Json => ['required', 'array'],
            SettingType::Color => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            SettingType::Image => ['nullable', 'string', 'max:2000'],
            default => ['nullable', 'string', 'max:2000'],
        };
    }

    protected function typeForKey(string $key): SettingType
    {
        $row = Setting::query()->where('key', $key)->first();

        if ($row?->type instanceof SettingType) {
            return $row->type;
        }

        $definition = config('marketplace.settings_defaults.'.$key, []);
        $type = $definition['type'] ?? null;

        if ($type instanceof SettingType) {
            return $type;
        }

        return SettingType::tryFrom((string) $type) ?? SettingType::String;
    }
}
