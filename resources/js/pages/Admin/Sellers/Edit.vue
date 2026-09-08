<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/admin/sellers';

type AdminSellerDetail = {
    id: number;
    store_name: string;
    slug: string;
    status: string;
    description?: string | null;
};

const props = defineProps<{
    seller: AdminSellerDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Stores',
                href: index(),
            },
        ],
    },
});

const statuses = ['active', 'pending', 'suspended'];

const form = reactive({
    status: props.seller.status ?? 'pending',
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
        const response = await fetch(
            `/api/v1/admin/sellers/${props.seller.id}`,
            {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ status: form.status }),
            },
        );

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
            notice.value = data.message ?? 'Store could not be saved.';
            return;
        }

        notice.value = 'Store saved.';
    } catch {
        notice.value = 'Stores are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <Head :title="`Edit ${seller.store_name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Stores
        </Link>
        <Heading
            variant="small"
            :title="`Edit ${seller.store_name}`"
            :description="`${seller.slug} · ${seller.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl rounded-xl border p-4"
        >
            <p class="text-muted-foreground mb-4 text-sm">
                {{ seller.description ?? 'No description.' }}
            </p>

            <p v-if="notice" class="mb-4 text-sm text-amber-600">{{ notice }}</p>

            <form class="space-y-5" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>
                    <InputError :message="errors.status" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">
                        {{ isSaving ? 'Saving…' : 'Save store' }}
                    </Button>
                    <a
                        :href="show(seller.id).url"
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
