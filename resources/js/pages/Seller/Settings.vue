<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getMalaysiaCities } from '@/composables/useMalaysiaCities';
import malaysiaStates from '@/data/malaysia-states.json';

type SellerSettingsDetail = {
    id: number;
    store_name: string;
    slug: string;
    status: string;
    description?: string | null;
    profile_photo_url?: string | null;
    phone?: string | null;
    whatsapp?: string | null;
    store_location?: string | null;
    bank_account?: string | null;
    state?: string | null;
    city?: string | null;
};

const props = defineProps<{
    seller: SellerSettingsDetail;
}>();

const malaysiaStateOptions: string[] = (
    malaysiaStates as { name: string }[]
).map((stateOption) => stateOption.name);

const form = reactive({
    store_name: props.seller.store_name,
    slug: props.seller.slug,
    description: props.seller.description ?? '',
    phone: props.seller.phone ?? '',
    whatsapp: props.seller.whatsapp ?? '',
    store_location: props.seller.store_location ?? '',
    bank_account: props.seller.bank_account ?? '',
    state: props.seller.state ?? '',
    city: props.seller.city ?? '',
});

const cityOptions = computed(() =>
    form.state === '' ? [] : getMalaysiaCities(form.state),
);

watch(
    () => form.state,
    (nextState, prevState) => {
        if (nextState !== prevState) {
            form.city = '';
        }
    },
);

const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const notice = ref<string | null>(null);
const newPhoto = ref<File | null>(null);
const removePhoto = ref(false);

const newPhotoPreview = computed(() =>
    newPhoto.value ? URL.createObjectURL(newPhoto.value) : null,
);

const visiblePhotoUrl = computed(() =>
    removePhoto.value
        ? null
        : (newPhotoPreview.value ?? props.seller.profile_photo_url ?? null),
);

function csrfToken(): string {
    return (
        (
            document.querySelector(
                'meta[name="csrf-token"]',
            ) as HTMLMetaElement | null
        )?.content ?? ''
    );
}

function onPhotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    newPhoto.value = file;
    if (file) {
        removePhoto.value = false;
    }
    input.value = '';
}

function clearNewPhoto(): void {
    newPhoto.value = null;
}

function toggleRemovePhoto(): void {
    removePhoto.value = !removePhoto.value;
    if (removePhoto.value) {
        newPhoto.value = null;
    }
}

async function save(): Promise<void> {
    isSaving.value = true;
    errors.value = {};
    notice.value = null;

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('store_name', form.store_name.trim());
    formData.append('slug', form.slug.trim());
    formData.append('description', form.description.trim());
    formData.append('phone', form.phone.trim());
    formData.append('whatsapp', form.whatsapp.trim());
    formData.append('store_location', form.store_location.trim());
    formData.append('bank_account', form.bank_account.trim());
    formData.append('state', form.state);
    formData.append('city', form.city);

    if (newPhoto.value) {
        formData.append('profile_photo', newPhoto.value);
    }

    if (removePhoto.value) {
        formData.append('remove_profile_photo', '1');
    }

    try {
        const response = await fetch('/api/v1/seller/settings', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: formData,
        });

        const data = (await response.json()) as {
            errors?: Record<string, string[]>;
            message?: string;
        };

        if (!response.ok) {
            const first: Record<string, string> = {};
            for (const [field, messages] of Object.entries(
                data.errors ?? {},
            )) {
                first[field] = messages[0] ?? 'Invalid value.';
            }
            errors.value = first;
            notice.value = data.message ?? 'Store settings could not be saved.';
            return;
        }

        notice.value = 'Store settings saved.';
        newPhoto.value = null;
        removePhoto.value = false;

        router.reload({ only: ['seller'] });
    } catch {
        notice.value = 'Store settings are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <Head title="Store settings" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Store settings"
            :description="`${seller.store_name} · ${seller.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl rounded-xl border p-4"
        >
            <p v-if="notice" class="mb-4 text-sm text-amber-600">
                {{ notice }}
            </p>

            <form class="space-y-5" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="store_name">Store name</Label>
                    <Input
                        id="store_name"
                        v-model="form.store_name"
                        type="text"
                    />
                    <InputError :message="errors.store_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug (optional)</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        placeholder="auto-generated"
                    />
                    <p class="text-muted-foreground text-xs">
                        Leave blank to auto-generate from the store name.
                    </p>
                    <InputError :message="errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="border-input min-h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label>Profile photo</Label>
                    <img
                        v-if="visiblePhotoUrl"
                        :src="visiblePhotoUrl"
                        :alt="seller.store_name"
                        class="h-20 w-20 rounded border object-cover"
                    />
                    <p v-else class="text-muted-foreground text-xs">
                        No profile photo.
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input
                            id="profile_photo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            @change="onPhotoChange"
                        />
                        <Button
                            v-if="newPhoto"
                            type="button"
                            variant="outline"
                            @click="clearNewPhoto"
                        >
                            Clear new photo
                        </Button>
                        <Button
                            v-if="
                                !newPhoto &&
                                (props.seller.profile_photo_url || removePhoto)
                            "
                            type="button"
                            variant="outline"
                            @click="toggleRemovePhoto"
                        >
                            {{ removePhoto ? 'Undo remove' : 'Remove photo' }}
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        JPG, PNG, or WebP. Max 2MB.
                    </p>
                    <InputError
                        :message="
                            errors['profile_photo'] ??
                            errors.profile_photo ??
                            errors.remove_profile_photo
                        "
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="phone">Phone number</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            placeholder="03-55123456"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="whatsapp">WhatsApp</Label>
                        <Input
                            id="whatsapp"
                            v-model="form.whatsapp"
                            type="text"
                            placeholder="60123456789"
                        />
                        <InputError :message="errors.whatsapp" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="store_location">Store location</Label>
                    <textarea
                        id="store_location"
                        v-model="form.store_location"
                        rows="2"
                        placeholder="No. 12, Jalan Meru, Klang, Selangor"
                        class="border-input min-h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.store_location" />
                </div>

                <div class="grid gap-2">
                    <Label for="bank_account">Bank account</Label>
                    <Input
                        id="bank_account"
                        v-model="form.bank_account"
                        type="text"
                        placeholder="Maybank a.n. Nama Pemilik Rekening 1234567890"
                    />
                    <p class="text-muted-foreground text-xs">
                        Example: Maybank a.n. Nama Pemilik Rekening 1234567890
                    </p>
                    <InputError :message="errors.bank_account" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="state">State</Label>
                        <select
                            id="state"
                            v-model="form.state"
                            class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option value="">No state</option>
                            <option
                                v-for="stateOption in malaysiaStateOptions"
                                :key="stateOption"
                                :value="stateOption"
                            >
                                {{ stateOption }}
                            </option>
                        </select>
                        <InputError :message="errors.state" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="city">City</Label>
                        <select
                            id="city"
                            v-model="form.city"
                            :disabled="
                                form.state === '' || cityOptions.length === 0
                            "
                            class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm disabled:opacity-50"
                        >
                            <option value="">
                                {{
                                    form.state === ''
                                        ? 'Select state first'
                                        : cityOptions.length === 0
                                          ? 'No cities available'
                                          : 'No city'
                                }}
                            </option>
                            <option
                                v-for="cityOption in cityOptions"
                                :key="cityOption"
                                :value="cityOption"
                            >
                                {{ cityOption }}
                            </option>
                        </select>
                        <InputError :message="errors.city" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">
                        {{ isSaving ? 'Saving…' : 'Save store settings' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
