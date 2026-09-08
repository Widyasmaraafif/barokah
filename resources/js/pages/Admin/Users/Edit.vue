<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getMalaysiaCities } from '@/composables/useMalaysiaCities';
import { index, show } from '@/routes/admin/users';
import malaysiaStates from '@/data/malaysia-states.json';

type AdminUserDetail = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    address?: string | null;
    state?: string | null;
    city?: string | null;
    post_code?: string | null;
    is_admin: boolean;
    is_active_as_seller: boolean;
};

const props = defineProps<{
    user: AdminUserDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Customers',
                href: index(),
            },
        ],
    },
});

const malaysiaStateOptions: string[] = (malaysiaStates as { name: string }[]).map(
    (stateOption) => stateOption.name,
);

const form = reactive({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
    address: props.user.address ?? '',
    state: props.user.state ?? '',
    city: props.user.city ?? '',
    post_code: props.user.post_code ?? '',
    is_admin: props.user.is_admin,
    is_active_as_seller: props.user.is_active_as_seller,
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

function csrfToken(): string {
    return (
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)
            ?.content ?? ''
    );
}

async function save(): Promise<void> {
    isSaving.value = true;
    errors.value = {};
    notice.value = null;

    try {
        const response = await fetch(`/api/v1/admin/users/${props.user.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                name: form.name,
                email: form.email,
                phone: form.phone.trim() === '' ? null : form.phone,
                address: form.address.trim() === '' ? null : form.address,
                state: form.state === '' ? null : form.state,
                city: form.city === '' ? null : form.city,
                post_code: form.post_code.trim() === '' ? null : form.post_code,
                is_admin: form.is_admin,
                is_active_as_seller: form.is_active_as_seller,
            }),
        });

        const data = (await response.json()) as {
            errors?: Record<string, string[]>;
            message?: string;
        };

        if (!response.ok) {
            const first: Record<string, string> = {};
            for (const [field, messages] of Object.entries(data.errors ?? {})) {
                first[field] = messages[0] ?? 'Invalid value.';
            }
            errors.value = first;
            notice.value = data.message ?? 'Customer could not be saved.';
            return;
        }

        notice.value = 'Customer saved.';
    } catch {
        notice.value = 'Customers are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Customers
        </Link>
        <Heading
            variant="small"
            :title="`Edit ${user.name}`"
            :description="user.email"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl rounded-xl border p-4"
        >
            <p v-if="notice" class="mb-4 text-sm text-amber-600">{{ notice }}</p>

            <form class="space-y-5" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" type="text" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input id="phone" v-model="form.phone" type="text" />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="address">Address</Label>
                    <Input id="address" v-model="form.address" type="text" />
                    <InputError :message="errors.address" />
                </div>

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
                        :disabled="form.state === '' || cityOptions.length === 0"
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

                <div class="grid gap-2">
                    <Label for="post_code">Post code</Label>
                    <Input id="post_code" v-model="form.post_code" type="text" />
                    <InputError :message="errors.post_code" />
                </div>

                <div class="flex items-center gap-2">
                    <Input
                        id="is_admin"
                        type="checkbox"
                        class="h-5 w-5"
                        :checked="form.is_admin"
                        @change="
                            form.is_admin = ($event.target as HTMLInputElement).checked
                        "
                    />
                    <Label for="is_admin">Admin capability</Label>
                </div>
                <InputError :message="errors.is_admin" />

                <div class="flex items-center gap-2">
                    <Input
                        id="is_active_as_seller"
                        type="checkbox"
                        class="h-5 w-5"
                        :checked="form.is_active_as_seller"
                        @change="
                            form.is_active_as_seller = (
                                $event.target as HTMLInputElement
                            ).checked
                        "
                    />
                    <Label for="is_active_as_seller">Seller activation</Label>
                </div>
                <InputError :message="errors.is_active_as_seller" />

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">
                        {{ isSaving ? 'Saving…' : 'Save customer' }}
                    </Button>
                    <a
                        :href="show(user.id).url"
                        rel="noopener"
                        class="text-muted-foreground text-sm hover:underline"
                    >
                        View detail
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>
