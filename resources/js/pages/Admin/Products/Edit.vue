<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/admin/products';
import { fetchAdminList } from '../useAdminList';

type AdminProductDetail = {
    id: number;
    name: string;
    slug: string;
    status: string;
    price: string | number;
    stock: number;
    description?: string | null;
    category?: { id: number; name: string; slug: string } | null;
};

type AdminCategoryOption = {
    id: number;
    name: string;
};

const props = defineProps<{
    product: AdminProductDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Products',
                href: index(),
            },
        ],
    },
});

const statuses = ['draft', 'active', 'inactive', 'archived'];

const form = reactive({
    status: props.product.status ?? 'draft',
    category_id:
        props.product.category?.id != null
            ? String(props.product.category.id)
            : '',
});

const categories = ref<AdminCategoryOption[]>([]);
const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const notice = ref<string | null>(null);

onMounted(async () => {
    try {
        categories.value = await fetchAdminList<AdminCategoryOption>(
            '/api/v1/admin/categories',
        );
    } catch {
        categories.value = [];
    }
});

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

    const payload: Record<string, unknown> = {
        status: form.status,
    };

    if (form.category_id !== '') {
        payload.category_id = Number(form.category_id);
    }

    try {
        const response = await fetch(
            `/api/v1/admin/products/${props.product.id}`,
            {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
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
            notice.value = data.message ?? 'Product could not be saved.';
            return;
        }

        notice.value = 'Product saved.';
    } catch {
        notice.value = 'Products are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}

async function removeProduct(): Promise<void> {
    if (!confirm(`Delete product "${props.product.name}"?`)) {
        return;
    }

    isSaving.value = true;
    notice.value = null;

    try {
        const response = await fetch(
            `/api/v1/admin/products/${props.product.id}`,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            },
        );

        if (!response.ok) {
            notice.value = 'Product could not be deleted.';
            return;
        }

        window.location.href = index().url;
    } catch {
        notice.value = 'Products are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Products
        </Link>
        <Heading
            variant="small"
            :title="`Edit ${product.name}`"
            :description="`${product.slug} · ${product.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl rounded-xl border p-4"
        >
            <p class="text-muted-foreground mb-4 text-sm">
                Price: {{ product.price }} · Stock: {{ product.stock }}
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

                <div class="grid gap-2">
                    <Label for="category_id">Category</Label>
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="">Keep current</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="errors.category_id" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">
                        {{ isSaving ? 'Saving…' : 'Save product' }}
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="isSaving"
                        @click="removeProduct"
                    >
                        Delete
                    </Button>
                    <a
                        :href="show(product.id).url"
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
