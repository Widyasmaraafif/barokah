<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { dashboard } from '@/routes/admin';

type AdminDashboardStats = {
    orders_count: number;
    revenue: string | number;
    revenue_formatted: string;
    currency_code: string;
    pending_payments: number;
    pending_orders: number;
    low_stock_count: number;
    seller_count: number;
    product_count: number;
};

type AdminRecentOrderItem = {
    id: number;
    product_name: string;
    quantity: number;
    subtotal: string | number;
};

type AdminRecentOrder = {
    id: number;
    order_number: string;
    status: string;
    total: string | number;
    created_at: string;
    items: AdminRecentOrderItem[];
};

const props = defineProps<{
    stats: AdminDashboardStats;
    recent_orders: AdminRecentOrder[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Admin dashboard',
                href: dashboard(),
            },
        ],
    },
});

const statsCards = computed(() => [
    { label: 'Orders', value: String(props.stats.orders_count) },
    { label: `Revenue (${props.stats.currency_code})`, value: props.stats.revenue_formatted },
    { label: 'Pending payments', value: String(props.stats.pending_payments) },
    { label: 'Pending orders', value: String(props.stats.pending_orders) },
    { label: 'Low stock products', value: String(props.stats.low_stock_count) },
    { label: 'Sellers', value: String(props.stats.seller_count) },
    { label: 'Products', value: String(props.stats.product_count) },
]);
</script>

<template>
    <Head title="Admin dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Admin dashboard"
            description="Marketplace overview: orders, revenue, payments, and stock"
        />

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                v-for="card in statsCards"
                :key="card.label"
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <p class="text-muted-foreground text-sm">{{ card.label }}</p>
                <p class="text-xl font-semibold tracking-tight">
                    {{ card.value }}
                </p>
            </div>
        </div>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <h3 class="mb-1 text-base font-medium">Recent orders</h3>
            <p class="text-muted-foreground mb-4 text-sm">
                Latest 10 customer orders with their items.
            </p>

            <p
                v-if="recent_orders.length === 0"
                class="text-muted-foreground text-sm"
            >
                No orders yet.
            </p>

            <ul v-else class="divide-y">
                <li
                    v-for="order in recent_orders"
                    :key="order.id"
                    class="py-3 first:pt-0 last:pb-0"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium">{{ order.order_number }}</p>
                        <p class="text-muted-foreground text-sm">
                            {{ order.status }}
                        </p>
                    </div>
                    <ul class="mt-2 space-y-1 text-sm">
                        <li
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <span>
                                {{ item.product_name }} × {{ item.quantity }}
                            </span>
                            <span>{{ item.subtotal }}</span>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</template>
