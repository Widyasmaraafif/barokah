<?php

namespace App\Services;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SettingsService
{
    public const CACHE_KEY = 'settings.all';

    public const PUBLIC_CACHE_KEY = 'settings.public';

    public const CACHE_TTL = 3600;

    /**
     * Get all settings with database values taking precedence over config defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        /** @var array<string, mixed> */
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $settings = $this->defaultValues();

            foreach (Setting::query()->orderBy('key')->get() as $setting) {
                $settings[$setting->key] = $this->decode($setting);
            }

            return $settings;
        });
    }

    /**
     * Get only public settings that are safe to expose to the frontend.
     *
     * @return array<string, mixed>
     */
    public function allPublic(): array
    {
        /** @var array<string, mixed> */
        return Cache::remember(self::PUBLIC_CACHE_KEY, self::CACHE_TTL, function (): array {
            $public = [];
            $privateKeys = [];

            foreach ($this->defaultDefinitions() as $key => $definition) {
                if (($definition['is_public'] ?? false) === true) {
                    $public[$key] = $this->decodeValue($definition['value'] ?? null, $definition['type'] ?? SettingType::String);
                }
            }

            foreach (Setting::query()->orderBy('key')->get() as $setting) {
                if ($setting->is_public) {
                    $public[$setting->key] = $this->decode($setting);
                } else {
                    $privateKeys[] = $setting->key;
                }
            }

            foreach ($privateKeys as $key) {
                unset($public[$key]);
            }

            return $public;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): Setting
    {
        $existing = Setting::query()->where('key', $key)->first();
        $definition = $this->defaultDefinitions()[$key] ?? [];

        $type = $existing?->type
            ?? $this->parseType($definition['type'] ?? null)
            ?? $this->inferType($value);
        $group = $existing?->group
            ?? $definition['group']
            ?? (str_contains($key, '.') ? Str::before($key, '.') : 'general');
        $isPublic = $existing?->is_public ?? $definition['is_public'] ?? false;

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->encode($value, $type),
                'type' => $type,
                'group' => $group,
                'is_public' => $isPublic,
            ],
        );

        $this->forget();

        return $setting;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::PUBLIC_CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultValues(): array
    {
        $values = [];

        foreach ($this->defaultDefinitions() as $key => $definition) {
            $values[$key] = $this->decodeValue($definition['value'] ?? null, $definition['type'] ?? SettingType::String);
        }

        return $values;
    }

    /**
     * @return array<string, array{value?: mixed, type?: mixed, group?: string, is_public?: bool}>
     */
    protected function defaultDefinitions(): array
    {
        /** @var array<string, array{value?: mixed, type?: mixed, group?: string, is_public?: bool}> */
        return config('marketplace.settings_defaults', []);
    }

    protected function decode(Setting $setting): mixed
    {
        return $this->decodeValue($setting->value, $setting->type);
    }

    protected function decodeValue(mixed $value, mixed $type): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $type instanceof SettingType ? $type : $this->parseType($type);

        return match ($type) {
            SettingType::Integer => (int) $value,
            SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingType::Json => is_array($value) ? $value : json_decode((string) $value, true),
            default => (string) $value,
        };
    }

    protected function encode(mixed $value, SettingType $type): string
    {
        return match ($type) {
            SettingType::Json => (string) json_encode($value),
            SettingType::Boolean => $value ? '1' : '0',
            SettingType::Integer => (string) (int) $value,
            default => (string) $value,
        };
    }

    protected function parseType(mixed $type): ?SettingType
    {
        if ($type instanceof SettingType) {
            return $type;
        }

        if (is_string($type) && $type !== '') {
            return SettingType::tryFrom($type);
        }

        return null;
    }

    protected function inferType(mixed $value): SettingType
    {
        if (is_bool($value)) {
            return SettingType::Boolean;
        }

        if (is_int($value)) {
            return SettingType::Integer;
        }

        if (is_array($value)) {
            return SettingType::Json;
        }

        return SettingType::String;
    }
}
