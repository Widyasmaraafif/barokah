<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
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

watch(
    () => props.settings,
    (settings) => {
        Object.assign(
            values,
            Object.fromEntries(settings.map((setting) => [setting.key, setting.value])),
        );
    },
    { deep: true },
);

const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const notice = ref<string | null>(null);
const logoFile = ref<File | null>(null);
const faviconFile = ref<File | null>(null);
const qrCodeFile = ref<File | null>(null);

function onBrandingFile(field: 'logo' | 'favicon', event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (field === 'logo') {
        logoFile.value = file;
    } else {
        faviconFile.value = file;
    }
}

function onQrCodeFile(event: Event): void {
    qrCodeFile.value = (event.target as HTMLInputElement).files?.[0] ?? null;
}

const groupLabels: Record<string, string> = {
    general: 'General',
    branding: 'Branding',
    currency: 'Currency',
    marketplace: 'Marketplace',
    checkout: 'Checkout',
    payment: 'Payment',
    shipping: 'Shipping',
    localization: 'Localization',
    contact: 'Contact',
    seo: 'SEO',
    email: 'Email',
};

const settingLabels: Record<string, string> = {
    'payment.bank_transfer_enabled': 'Bank Transfer',
    'payment.bank_name': 'Bank Name',
    'payment.bank_account_name': 'Account Holder Name',
    'payment.bank_account_number': 'Account Number',
    'payment.qr_code_enabled': 'QR Code',
    'payment.qr_code_url': 'QR Code Image',
    'payment.paynet_enabled': 'Payment Gateway (PayNet)',
    'branding.logo_url': 'Website Logo',
    'branding.favicon_url': 'Website Favicon',
    'branding.site_name': 'Website Name',
    'branding.primary_color': 'Primary Color',
    'branding.primary_hover_color': 'Primary Hover Color',
    'branding.primary_soft_color': 'Primary Soft Color',
    'branding.secondary_color': 'Secondary Color',
    'general.site_tagline': 'Website Tagline',
    'general.maintenance_mode': 'Maintenance Mode',
    'general.allow_registration': 'Allow User Registration',
    'currency.code': 'Currency Code',
    'currency.symbol': 'Currency Symbol',
    'currency.decimals': 'Decimal Places',
    'currency.thousands_separator': 'Thousands Separator',
    'currency.decimal_separator': 'Decimal Separator',
    'marketplace.name': 'Marketplace Name',
    'marketplace.description': 'Marketplace Description',
    'marketplace.status': 'Marketplace Status',
    'marketplace.seller_registration_enabled': 'Allow Seller Registration',
    'marketplace.reviews_enabled': 'Enable Product Reviews',
    'marketplace.inventory_tracking_enabled': 'Enable Inventory Tracking',
    'checkout.order_expiration_minutes': 'Order Payment Expiration (Minutes)',
    'checkout.min_order_amount': 'Minimum Order Amount',
    'checkout.max_order_amount': 'Maximum Order Amount',
    'checkout.guest_checkout_enabled': 'Allow Guest Checkout',
    'payment.fpx_enabled': 'FPX',
    'payment.duitnow_enabled': 'DuitNow',
    'payment.gateway': 'Payment Gateway Name',
    'payment.merchant_id': 'Merchant ID',
    'payment.secret_key': 'Secret Key',
    'payment.sandbox_enabled': 'Sandbox Mode',
    'payment.api_base': 'Payment Gateway API URL',
    'shipping.method': 'Shipping Method',
    'shipping.fixed_rate': 'Fixed Shipping Rate',
    'shipping.free_shipping_enabled': 'Enable Free Shipping',
    'shipping.free_shipping_threshold': 'Free Shipping Minimum Amount',
    'shipping.provider_name': 'Shipping Provider Name',
    'shipping.api_enabled': 'Enable Shipping API',
    'shipping.api_key': 'Shipping API Key',
    'shipping.api_secret': 'Shipping API Secret',
    'shipping.api_base_url': 'Shipping API URL',
    'localization.default_language': 'Default Language',
    'localization.available_languages': 'Available Languages',
    'localization.timezone': 'Time Zone',
    'localization.date_format': 'Date Format',
    'localization.time_format': 'Time Format',
    'localization.gtranslate_enabled': 'Enable Google Translate',
    'contact.email': 'Contact Email',
    'contact.phone': 'Contact Phone Number',
    'contact.address': 'Contact Address',
    'contact.business_hours': 'Business Hours',
    'contact.whatsapp': 'Contact WhatsApp Number',
    'seo.meta_title': 'SEO Meta Title',
    'seo.meta_description': 'SEO Meta Description',
    'seo.keywords': 'SEO Keywords',
    'seo.og_image': 'Open Graph Image',
    'email.from_name': 'Email Sender Name',
    'email.from_address': 'Email Sender Address',
    'email.smtp_host': 'SMTP Host',
    'email.smtp_port': 'SMTP Port',
    'email.smtp_username': 'SMTP Username',
    'email.smtp_password': 'SMTP Password',
    'email.smtp_encryption': 'SMTP Encryption',
};

const groupLabel = computed(() => groupLabels[props.activeGroup] ?? props.activeGroup);

const visibleSettings = computed(() => {
    if (props.activeGroup !== 'payment') {
        return props.settings;
    }

    const paymentOrder = [
        'payment.bank_transfer_enabled',
        'payment.bank_name',
        'payment.bank_account_name',
        'payment.bank_account_number',
        'payment.qr_code_enabled',
        'payment.qr_code_url',
        'payment.paynet_enabled',
        'payment.gateway',
        'payment.merchant_id',
        'payment.secret_key',
        'payment.sandbox_enabled',
        'payment.api_base',
        'payment.fpx_enabled',
        'payment.duitnow_enabled',
    ];

    return props.settings
        .filter((setting) => {
            if (setting.key === 'payment.bank_transfer_enabled') {
                return true;
            }

            if (setting.key === 'payment.qr_code_enabled') {
                return true;
            }

            if (setting.key === 'payment.paynet_enabled') {
                return true;
            }

            if (setting.key.startsWith('payment.bank_')) {
                return Boolean(values['payment.bank_transfer_enabled']);
            }

            if (setting.key === 'payment.qr_code_url') {
                return Boolean(values['payment.qr_code_enabled']);
            }

            return Boolean(values['payment.paynet_enabled']);
        })
        .sort((first, second) => paymentOrder.indexOf(first.key) - paymentOrder.indexOf(second.key));
});

function settingLabel(key: string): string {
    if (settingLabels[key]) {
        return settingLabels[key];
    }

    const name = key.split('.').at(-1) ?? key;

    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

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
        router.reload({ only: ['settings'] });
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
            description="Manage website configuration with clear labels. Secret values remain hidden."
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
                {{ groupLabels[group] ?? group }}
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
                No settings are available in this group yet.
            </p>

            <form v-else class="space-y-6" @submit.prevent="save">
                <div v-for="setting in visibleSettings" :key="setting.key" class="grid gap-2">
                    <Label :for="setting.key">
                        {{ settingLabel(setting.key) }}
                        <span v-if="setting.masked" class="text-muted-foreground">
                            (secret value hidden as {{ MASKED_SENTINEL }})
                        </span>
                    </Label>

                    <div v-if="inputKind(setting.type) === 'checkbox'" class="flex items-center gap-3">
                        <Input
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
                        <span class="text-sm">Enable {{ settingLabel(setting.key) }}</span>
                    </div>
                    <Input
                        v-else-if="inputKind(setting.type) === 'color'"
                        :id="setting.key"
                        type="color"
                        class="h-10 w-20"
                        :model-value="String(values[setting.key] ?? '#000000')"
                        @update:model-value="values[setting.key] = $event"
                    />
                    <template v-else-if="activeGroup === 'branding' && setting.key === 'branding.logo_url'">
                        <img v-if="values[setting.key]" :src="String(values[setting.key])" alt="Logo preview" class="h-16 max-w-48 rounded border object-contain p-2" />
                        <Input :id="setting.key" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="onBrandingFile('logo', $event)" />
                        <p class="text-muted-foreground text-xs">PNG, JPG, WebP, or SVG. Max 2MB.</p>
                    </template>
                    <template v-else-if="activeGroup === 'branding' && setting.key === 'branding.favicon_url'">
                        <img v-if="values[setting.key]" :src="String(values[setting.key])" alt="Favicon preview" class="h-12 w-12 rounded border object-contain p-2" />
                        <Input :id="setting.key" type="file" accept="image/png,image/jpeg,image/webp,image/x-icon,image/svg+xml" @change="onBrandingFile('favicon', $event)" />
                        <p class="text-muted-foreground text-xs">PNG, JPG, WebP, ICO, or SVG. Max 1MB.</p>
                    </template>
                    <template v-else-if="activeGroup === 'payment' && setting.key === 'payment.qr_code_url'">
                        <img v-if="values[setting.key]" :src="String(values[setting.key])" alt="QR code preview" class="h-48 w-48 rounded border object-contain p-2" />
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
