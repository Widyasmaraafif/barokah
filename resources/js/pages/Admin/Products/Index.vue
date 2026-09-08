<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, edit, show } from '@/routes/admin/products';
import { formatPrice } from '@/services/priceFormatter';
import { fetchAdminList } from '../useAdminList';

type AdminProduct = {
    id: number;
    name: string;
    slug: string;
    status: string;
    price: string | number;
    stock: number;
    seller?: { id: number; store_name: string } | null;
    category?: { id: number; name: string } | null;
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

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'archived':
            return 'destructive';
        case 'inactive':
            return 'outline';
        default:
            return 'secondary';
    }
}

function displayPrice(price: string | number): string {
    const amount = typeof price === 'number' ? price : Number(price);

    return Number.isFinite(amount) ? formatPrice(amount) : String(price);
}

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

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead>
                        <tr
                            class="text-muted-foreground border-b font-medium"
                        >
                            <th class="px-3 py-2 font-medium">Product</th>
                            <th class="px-3 py-2 font-medium">Store</th>
                            <th class="px-3 py-2 font-medium">Category</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Price
                            </th>
                            <th class="px-3 py-2 text-right font-medium">
                                Stock
                            </th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products"
                            :key="product.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="show(product.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ product.name }}
                                </Link>
                                <p
                                    class="text-muted-foreground text-xs"
                                >
                                    {{ product.slug }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                {{ product.seller?.store_name ?? '-' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ product.category?.name ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ displayPrice(product.price) }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ product.stock }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge :variant="statusVariant(product.status)">
                                    {{ product.status }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <Link
                                        :href="show(product.id)"
                                        class="text-muted-foreground text-sm hover:underline"
                                    >
                                        Detail
                                    </Link>
                                    <a
                                        :href="edit(product.id).url"
                                        rel="noopener"
                                        class="text-sm font-medium hover:underline"
                                    >
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
