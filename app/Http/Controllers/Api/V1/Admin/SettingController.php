<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateAdminSettingsRequest;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Admin settings API (spec §11.8): list all settings with private values
 * masked, update in batch or single mode with per-key validation and
 * sanitization. Writes invalidate the settings cache so the public
 * endpoint and formatters pick up changes immediately.
 */
class SettingController extends Controller
{
    /**
     * List settings, optionally filtered by group, with private masked.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Setting::class);

        $validated = $request->validate([
            'group' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = Setting::query()
            ->when($validated['group'] ?? null, fn ($query, $group) => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return SettingResource::collection($settings);
    }

    /**
     * Update one or many settings with per-key rules and sanitization.
     */
    public function update(UpdateAdminSettingsRequest $request, SettingsService $settings): JsonResponse
    {
        $updated = [];
        $uploadedBranding = [
            'branding.logo_url' => $request->file('branding_logo'),
            'branding.favicon_url' => $request->file('branding_favicon'),
            'payment.qr_code_url' => $request->file('payment_qr_code'),
        ];

        DB::transaction(function () use ($request, $settings, $uploadedBranding, &$updated): void {
            foreach ($uploadedBranding as $key => $file) {
                if ($file === null) {
                    continue;
                }

                $oldPath = Setting::query()->where('key', $key)->value('value');
                if (is_string($oldPath) && $oldPath !== '') {
                    Storage::disk('public')->delete($oldPath);
                }

                $updated[] = $settings->set($key, $file->store($key === 'payment.qr_code_url' ? 'payment' : 'branding', 'public'));
            }

            foreach ($request->pairs() as $key => $value) {
                if (array_key_exists($key, $uploadedBranding)) {
                    continue;
                }
                if ($request->isUnchangedPrivate($key, $value)) {
                    continue;
                }

                $updated[] = $settings->set($key, $request->sanitizedValue($key, $value));
            }
        });

        $settings->forget();

        return response()->json([
            'data' => SettingResource::collection(collect($updated)),
        ]);
    }
}
