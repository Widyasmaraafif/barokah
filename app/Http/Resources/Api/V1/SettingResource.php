<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Admin setting entry (spec §11.8).
 *
 * Private values are masked so secrets never leak to the browser in full;
 * the admin UI sends the sentinel back untouched when no change is intended.
 *
 * @mixin Setting
 */
class SettingResource extends JsonResource
{
    public const MASKED_SENTINEL = '••••••••';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPublic = (bool) $this->is_public;
        $value = $this->decodedValue();
        if (in_array($this->key, ['branding.logo_url', 'branding.favicon_url', 'payment.qr_code_url'], true) && is_string($value) && $value !== '') {
            $value = str_starts_with($value, 'http') ? $value : Storage::disk('public')->url($value);
        }

        return [
            'key' => $this->key,
            'value' => $isPublic ? $value : self::MASKED_SENTINEL,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'group' => $this->group,
            'is_public' => $isPublic,
            'masked' => ! $isPublic,
        ];
    }

    protected function decodedValue(): mixed
    {
        return app(SettingsService::class)->get($this->key, $this->value);
    }
}
