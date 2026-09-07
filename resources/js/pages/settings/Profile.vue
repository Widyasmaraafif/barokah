<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

type SellerProfile = {
    store_name: string;
    slug: string;
    status: string;
} | null;

const seller = ref<SellerProfile>(null);
const storeName = ref('');
const description = ref('');
const activationError = ref<string | null>(null);
const fieldErrors = ref<Record<string, string>>({});
const isActivating = ref(false);

const isSeller = computed(() => {
    const flags = user.value as Record<string, unknown>;
    return (
        flags.is_seller === true ||
        flags.is_active_as_seller === true ||
        seller.value !== null
    );
});

onMounted(async () => {
    try {
        const response = await fetch('/api/v1/me', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        const payload = (await response.json()) as {
            data?: { seller?: SellerProfile };
        };
        seller.value = payload.data?.seller ?? null;
    } catch {
        // Seller status stays derived from shared auth props.
    }
});

async function activateAsSeller(): Promise<void> {
    activationError.value = null;
    fieldErrors.value = {};
    isActivating.value = true;

    try {
        const response = await fetch('/api/v1/seller/activate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                store_name: storeName.value,
                description: description.value || undefined,
            }),
        });

        const payload = (await response.json()) as {
            data?: SellerProfile;
            message?: string;
            errors?: Record<string, string[]>;
        };

        if (response.status === 422 && payload.errors) {
            const flat: Record<string, string> = {};

            for (const [key, messages] of Object.entries(payload.errors)) {
                flat[key] = messages[0] ?? 'Invalid value.';
            }

            fieldErrors.value = flat;
            return;
        }

        if (!response.ok || !payload.data) {
            activationError.value =
                payload.message ?? 'Seller activation failed.';
            return;
        }

        seller.value = payload.data;
    } catch {
        activationError.value = 'Seller activation failed. Please try again.';
    } finally {
        isActivating.value = false;
    }
}
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name and email address"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="text-muted-foreground -mt-4 text-sm">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <div class="mt-6 flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Active as Seller"
            description="Activate your store to start selling"
        />

        <div v-if="isSeller || seller" class="text-sm">
            <p class="text-muted-foreground">
                {{
                    seller
                        ? `${seller.store_name} · ${seller.status}`
                        : 'Your store is active.'
                }}
            </p>
            <Link
                :href="sellerDashboard()"
                class="text-foreground mt-2 inline-block underline decoration-neutral-300 underline-offset-4"
            >
                Go to Seller dashboard
            </Link>
        </div>

        <form v-else class="space-y-4" @submit.prevent="activateAsSeller">
            <div class="grid gap-2">
                <Label for="store-name">Store name</Label>
                <Input
                    id="store-name"
                    v-model="storeName"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    placeholder="Barokah Store"
                />
                <InputError class="mt-2" :message="fieldErrors.store_name" />
            </div>

            <div class="grid gap-2">
                <Label for="store-description">Description (optional)</Label>
                <Input
                    id="store-description"
                    v-model="description"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Keripik and hijab store"
                />
                <InputError class="mt-2" :message="fieldErrors.description" />
            </div>

            <p v-if="activationError" class="text-sm text-red-600">
                {{ activationError }}
            </p>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="isActivating"
                    data-test="activate-seller-button"
                >
                    {{ isActivating ? 'Activating...' : 'Active as Seller' }}
                </Button>
            </div>
        </form>
    </div>

    <DeleteUser />
</template>
