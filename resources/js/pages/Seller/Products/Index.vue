<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { index, create, edit } from '@/routes/seller/products';
import { formatPrice } from '@/services/priceFormatter';

type SellerProduct = {
    id: number;
    name: string;
    slug: string;
    status: string;
    price: string | number;
    stock: number;
    category?: { name: string } | null;
};

const products = ref<SellerProduct[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

function csrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

function displayPrice(price: string | number): string {
    return formatPrice(Number(price));
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    return status === 'active' ? 'default' : status === 'archived' ? 'destructive' : 'secondary';
}

async function loadProducts(): Promise<void> {
    try {
        const response = await fetch('/api/v1/seller/products', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error();
        const data = (await response.json()) as { data: SellerProduct[] };
        products.value = data.data;
    } catch {
        error.value = 'Products are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
}

async function removeProduct(product: SellerProduct): Promise<void> {
    if (!confirm(`Delete product "${product.name}"?`)) return;

    const response = await fetch(`/api/v1/seller/products/${product.id}`, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });

    if (response.ok) {
        products.value = products.value.filter(({ id }) => id !== product.id);
    }
}

onMounted(loadProducts);
</script>

<template>
    <Head title="Seller products" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading variant="small" title="Seller products" description="Manage products owned by your store." />
            <Button as-child><Link :href="create()">Add product</Link></Button>
        </div>
        <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <div v-if="isLoading" class="animate-pulse space-y-2"><div class="bg-muted h-10 rounded" /><div class="bg-muted h-10 rounded" /></div>
            <p v-else-if="error" class="text-sm text-amber-600">{{ error }}</p>
            <p v-else-if="products.length === 0" class="text-muted-foreground text-sm">No products yet.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead><tr class="text-muted-foreground border-b"><th class="px-3 py-2">Product</th><th class="px-3 py-2">Category</th><th class="px-3 py-2 text-right">Price</th><th class="px-3 py-2 text-right">Stock</th><th class="px-3 py-2">Status</th><th class="px-3 py-2 text-right">Actions</th></tr></thead>
                    <tbody>
                        <tr v-for="product in products" :key="product.id" class="border-b last:border-0">
                            <td class="px-3 py-2"><div class="font-medium">{{ product.name }}</div><div class="text-muted-foreground text-xs">{{ product.slug }}</div></td>
                            <td class="px-3 py-2">{{ product.category?.name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ displayPrice(product.price) }}</td>
                            <td class="px-3 py-2 text-right">{{ product.stock }}</td>
                            <td class="px-3 py-2"><Badge :variant="statusVariant(product.status)">{{ product.status }}</Badge></td>
                            <td class="px-3 py-2 text-right"><Link :href="edit(product.id)" class="mr-3 hover:underline">Edit</Link><button class="text-destructive hover:underline" @click="removeProduct(product)">Delete</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
