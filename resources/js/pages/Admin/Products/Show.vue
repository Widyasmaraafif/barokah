<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/products';

type AdminProductDetail = {
    id: number;
    name: string;
    slug: string;
    status: string;
    price: string | number;
    stock: number;
    description?: string | null;
};

defineProps<{
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
</script>

<template>
    <Head title="Product detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            :title="product.name"
            :description="`${product.slug} · ${product.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <p class="text-sm">Price: {{ product.price }}</p>
            <p class="text-muted-foreground mt-1 text-sm">
                Stock: {{ product.stock }}
            </p>
            <p class="text-muted-foreground mt-1 text-sm">
                {{ product.description ?? 'No description.' }}
            </p>
        </div>
    </div>
</template>
