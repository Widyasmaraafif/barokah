<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/products';
import { fetchAdminList } from '../useAdminList';

type AdminProduct = {
    id: number;
    name: string;
    status: string;
    price: string | number;
    stock: number;
};

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

const products = ref<AdminProduct[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        products.value = await fetchAdminList<AdminProduct>('/api/v1/admin/products');
    } catch {
        error.value = 'Products are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Products" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Products"
            description="Moderate any product: status and category assignment."
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <div v-if="isLoading" class="animate-pulse space-y-2">
                <div class="bg-muted h-10 rounded" />
                <div class="bg-muted h-10 rounded" />
                <div class="bg-muted h-10 rounded" />
            </div>

            <p v-else-if="error" class="text-sm text-amber-600">{{ error }}</p>

            <p
                v-else-if="products.length === 0"
                class="text-muted-foreground text-sm"
            >
                No products yet.
            </p>

            <ul v-else class="divide-y">
                <li
                    v-for="product in products"
                    :key="product.id"
                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                >
                    <div>
                        <p class="font-medium">{{ product.name }}</p>
                        <p class="text-muted-foreground text-sm">
                            Stock: {{ product.stock }}
                        </p>
                    </div>
                    <p class="text-muted-foreground text-sm">{{ product.status }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>
