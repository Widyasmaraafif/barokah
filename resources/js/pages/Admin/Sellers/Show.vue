<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, edit } from '@/routes/admin/sellers';
import { show as showProduct } from '@/routes/admin/products';
import { formatPrice } from '@/services/priceFormatter';

type AdminSellerOwner = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    is_active_as_seller: boolean;
} | null;

type AdminSellerProduct = {
    id: number;
    name: string;
    slug: string;
    price: string | number;
    stock: number;
    status: string;
};

type AdminSellerDetail = {
    id: number;
    store_name: string;
    slug: string;
    status: string;
    description?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    owner: AdminSellerOwner;
    products: AdminSellerProduct[];
};

defineProps<{
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

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'suspended':
            return 'destructive';
        default:
            return 'secondary';
    }
}

function displayMoney(value: string | number): string {
    const amount = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(amount) ? formatPrice(amount) : String(value);
}

function displayText(value: string | null | undefined): string {
    return value === null || value === undefined || value === '' ? '-' : value;
}
</script>

<template>
    <Head title="Store detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Stores
        </Link>
        <Heading
            variant="small"
            :title="seller.store_name"
            :description="`${seller.slug} · ${seller.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <Badge :variant="statusVariant(seller.status)">
                    {{ seller.status }}
                </Badge>
                <a
                    :href="edit(seller.id).url"
                    rel="noopener"
                    class="text-sm font-medium hover:underline"
                >
                    Edit
                </a>
            </div>

            <table class="w-full text-left text-sm">
                <tbody>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            ID
                        </th>
                        <td class="px-3 py-2">{{ seller.id }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Store name
                        </th>
                        <td class="px-3 py-2">{{ seller.store_name }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Slug
                        </th>
                        <td class="px-3 py-2">{{ seller.slug }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Description
                        </th>
                        <td class="px-3 py-2">
                            {{ seller.description ?? 'No description.' }}
                        </td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Created at
                        </th>
                        <td class="px-3 py-2">
                            {{ displayText(seller.created_at) }}
                        </td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Updated at
                        </th>
                        <td class="px-3 py-2">
                            {{ displayText(seller.updated_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <h3 class="mb-3 text-base font-medium">Owner</h3>
            <table v-if="seller.owner" class="w-full text-left text-sm">
                <tbody>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Name
                        </th>
                        <td class="px-3 py-2">{{ seller.owner.name }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Email
                        </th>
                        <td class="px-3 py-2">{{ seller.owner.email }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Phone
                        </th>
                        <td class="px-3 py-2">
                            {{ displayText(seller.owner.phone) }}
                        </td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Activation
                        </th>
                        <td class="px-3 py-2">
                            <Badge
                                :variant="
                                    seller.owner.is_active_as_seller
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{
                                    seller.owner.is_active_as_seller
                                        ? 'Seller active'
                                        : 'Not active'
                                }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="text-muted-foreground text-sm">No owner.</p>
        </div>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <h3 class="mb-1 text-base font-medium">
                Products ({{ seller.products.length }})
            </h3>
            <p class="text-muted-foreground mb-4 text-sm">
                All products owned by this store.
            </p>

            <p
                v-if="seller.products.length === 0"
                class="text-muted-foreground text-sm"
            >
                No products yet.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Product</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Price
                            </th>
                            <th class="px-3 py-2 text-right font-medium">
                                Stock
                            </th>
                            <th class="px-3 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in seller.products"
                            :key="product.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="showProduct(product.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ product.name }}
                                </Link>
                                <p class="text-muted-foreground text-xs">
                                    {{ product.slug }}
                                </p>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ displayMoney(product.price) }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ product.stock }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge :variant="statusVariant(product.status)">
                                    {{ product.status }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
