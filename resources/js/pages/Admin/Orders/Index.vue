<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, show } from '@/routes/admin/orders';
import { formatPrice } from '@/services/priceFormatter';
import { fetchAdminList } from '../useAdminList';

type AdminOrderListItem = {
    id: number;
    order_number: string;
    status: string;
    total: string | number;
    customer_name?: string | null;
    created_at: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Customer orders',
                href: index(),
            },
        ],
    },
});

const orders = ref<AdminOrderListItem[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

function displayTotal(total: string | number): string {
    const amount = typeof total === 'number' ? total : Number(total);

    return Number.isFinite(amount) ? formatPrice(amount) : String(total);
}

onMounted(async () => {
    try {
        orders.value = await fetchAdminList<AdminOrderListItem>('/api/v1/admin/orders');
    } catch {
        error.value = 'Customer orders are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Customer orders" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Customer orders"
            description="All customer orders with status filter."
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
                v-else-if="orders.length === 0"
                class="text-muted-foreground text-sm"
            >
                No customer orders yet.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Order</th>
                            <th class="px-3 py-2 font-medium">Customer</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Total
                            </th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders"
                            :key="order.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="show(order.order_number)"
                                    class="font-medium hover:underline"
                                >
                                    {{ order.order_number }}
                                </Link>
                                <p class="text-muted-foreground text-xs">
                                    {{ order.created_at }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                {{ order.customer_name ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ displayTotal(order.total) }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge variant="secondary">
                                    {{ order.status }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <Link
                                        :href="show(order.order_number)"
                                        class="text-muted-foreground text-sm hover:underline"
                                    >
                                        Detail
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
