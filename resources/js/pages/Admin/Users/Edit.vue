<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/admin/users';

type AdminUserDetail = {
    id: number;
    name: string;
    email: string;
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

const form = reactive({
    is_admin: props.user.is_admin,
    is_active_as_seller: props.user.is_active_as_seller,
});

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
                        target="_blank"
                        rel="noopener"
                        class="text-muted-foreground text-sm hover:underline"
                    >
                        View detail in new tab
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>
