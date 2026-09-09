<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { show } from '@/routes/admin/settings';

type AdminSettingEntry = {
    key: string;
    value: string | number | boolean | string[] | null;
    type: string;
    group: string;
    is_public: boolean;
    masked: boolean;
};

const props = defineProps<{
    groups: string[];
    activeGroup: string;
    settings: AdminSettingEntry[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: show(),
            },
        ],
    },
});

const MASKED_SENTINEL = '••••••••';

const values = reactive<Record<string, string | number | boolean | string[] | null>>(
    Object.fromEntries(props.settings.map((setting) => [setting.key, setting.value])),
);

const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const notice = ref<string | null>(null);
const logoFile = ref<File | null>(null);
const faviconFile = ref<File | null>(null);
const logoPreview = ref<string | null>(null);
const faviconPreview = ref<string | null>(null);
const qrCodeFile = ref<File | null>(null);
const qrCodePreview = ref<string | null>(null);

function onBrandingFile(field: 'logo' | 'favicon', event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (field === 'logo') {
        logoFile.value = file;
        logoPreview.value = file ? URL.createObjectURL(file) : null;
    } else {
        faviconFile.value = file;
        faviconPreview.value = file ? URL.createObjectURL(file) : null;
    }
}

function onQrCodeFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    qrCodeFile.value = file;
    qrCodePreview.value = file ? URL.createObjectURL(file) : null;
}

const groupLabel = computed(() => props.activeGroup);

function inputKind(type: string): 'checkbox' | 'color' | 'text' {
    if (type === 'boolean') {
        return 'checkbox';
    }

    if (type === 'color') {
        return 'color';
    }

    return 'text';
}

async function save(): Promise<void> {
    isSaving.value = true;
    errors.value = {};
    notice.value = null;

    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('settings', JSON.stringify(props.settings.map((setting) => ({
            key: setting.key,
            value: values[setting.key] ?? null,
        }))));
        if (logoFile.value) formData.append('branding_logo', logoFile.value);
        if (faviconFile.value) formData.append('branding_favicon', faviconFile.value);
        if (qrCodeFile.value) formData.append('payment_qr_code', qrCodeFile.value);

        const response = await fetch('/api/v1/admin/settings', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)
                        ?.content ?? '',
            },
            body: formData,
        });

        const payload = (await response.json()) as {
            errors?: Record<string, string[]>;
            message?: string;
        };

        if (!response.ok) {
            const first: Record<string, string> = {};

            for (const [field, messages] of Object.entries(payload.errors ?? {})) {
                first[field] = messages[0] ?? 'Invalid value.';
            }

            errors.value = first;
            notice.value = payload.message ?? 'Settings could not be saved.';
            return;
        }

        notice.value = 'Settings saved.';
    } catch {
        notice.value = 'Settings are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <Head title="Settings" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Settings"
            description="11 categories. Private values are masked; send the mask back untouched to keep the secret."
        />

        <nav class="flex flex-wrap gap-2" aria-label="Settings groups">
            <Link
                v-for="group in groups"
                :key="group"
                :href="show(group)"
                class="rounded-md border px-3 py-1 text-sm"
                :class="
                    group === activeGroup
                        ? 'border-primary font-medium'
                        : 'text-muted-foreground'
                "
            >
                {{ group }}
            </Link>
        </nav>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <h3 class="mb-4 text-base font-medium capitalize">{{ groupLabel }}</h3>

            <p v-if="notice" class="mb-4 text-sm text-amber-600">{{ notice }}</p>

            <p
                v-if="settings.length === 0"
                class="text-muted-foreground text-sm"
            >
                No settings in this group yet.
            </p>

            <form v-else class="space-y-6" @submit.prevent="save">
                <div v-for="setting in settings" :key="setting.key" class="grid gap-2">
                    <Label :for="setting.key">
                        {{ setting.key }}
                        <span v-if="setting.masked" class="text-muted-foreground">
                            (private, masked as {{ MASKED_SENTINEL }})
                        </span>
                    </Label>

                    <Input
                        v-if="inputKind(setting.type) === 'checkbox'"
                        :id="setting.key"
                        type="checkbox"
                        class="h-5 w-5"
                        :checked="Boolean(values[setting.key])"
                        @change="
                            values[setting.key] = (
                                $event.target as HTMLInputElement
                            ).checked
                        "
                    />
                    <Input
                        v-else-if="inputKind(setting.type) === 'color'"
                        :id="setting.key"
                        type="color"
                        class="h-10 w-20"
                        :model-value="String(values[setting.key] ?? '#000000')"
                        @update:model-value="values[setting.key] = $event"
                    />
                    <template v-else-if="activeGroup === 'branding' && setting.key === 'branding.logo_url'">
                        <img v-if="logoPreview || values[setting.key]" :src="logoPreview || String(values[setting.key])" alt="Logo preview" class="h-16 max-w-48 rounded border object-contain p-2" />
                        <Input :id="setting.key" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="onBrandingFile('logo', $event)" />
                        <p class="text-muted-foreground text-xs">PNG, JPG, WebP, or SVG. Max 2MB.</p>
                    </template>
                    <template v-else-if="activeGroup === 'branding' && setting.key === 'branding.favicon_url'">
                        <img v-if="faviconPreview || values[setting.key]" :src="faviconPreview || String(values[setting.key])" alt="Favicon preview" class="h-12 w-12 rounded border object-contain p-2" />
                        <Input :id="setting.key" type="file" accept="image/png,image/jpeg,image/webp,image/x-icon,image/svg+xml" @change="onBrandingFile('favicon', $event)" />
                        <p class="text-muted-foreground text-xs">PNG, JPG, WebP, ICO, or SVG. Max 1MB.</p>
                    </template>
                    <template v-else-if="activeGroup === 'payment' && setting.key === 'payment.qr_code_url'">
                        <img v-if="qrCodePreview || values[setting.key]" :src="qrCodePreview || String(values[setting.key])" alt="QR code preview" class="h-48 w-48 rounded border object-contain p-2" />
                        <Input :id="setting.key" type="file" accept="image/png,image/jpeg,image/webp" @change="onQrCodeFile" />
                        <p class="text-muted-foreground text-xs">PNG, JPG, or WebP. Max 2MB.</p>
                    </template>
                    <Input
                        v-else
                        :id="setting.key"
                        type="text"
                        class="block w-full"
                        :model-value="
                            Array.isArray(values[setting.key])
                                ? (values[setting.key] as string[]).join(',')
                                : String(values[setting.key] ?? '')
                        "
                        @update:model-value="values[setting.key] = $event"
                    />
                    <InputError
                        class="mt-2"
                        :message="errors[`settings.${setting.key}`]"
                    />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">Save</Button>
                </div>
            </form>
        </div>
    </div>
</template>
