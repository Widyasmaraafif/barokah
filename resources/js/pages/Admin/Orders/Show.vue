<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/orders';

type AdminOrderDetailItem = {
    id: number;
    product_name: string;
    quantity: number;
    subtotal: string | number;
};

type AdminOrderDetail = {
    id: number;
    order_number: string;
    status: string;
    total: string | number;
    items: AdminOrderDetailItem[];
};

defineProps<{
    order: AdminOrderDetail;
}>();

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
</script>

<template>
    <Head title="Customer order detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            :title="order.order_number"
            :description="order.status"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <p class="text-sm">Total: {{ order.total }}</p>
            <ul class="mt-2 space-y-1 text-sm">
                <li
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex items-center justify-between gap-2"
                >
                    <span>{{ item.product_name }} × {{ item.quantity }}</span>
                    <span>{{ item.subtotal }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
