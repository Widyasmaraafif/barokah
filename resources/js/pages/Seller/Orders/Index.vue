<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index as ordersIndex, show } from '@/routes/seller/orders';

type Order = {
    id: number;
    order_number: string;
    status: string;
    tracking_status?: string | null;
    created_at: string;
};

const orders = ref<Order[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        const response = await fetch('/api/v1/seller/orders', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Orders unavailable.');
        }

        orders.value = ((await response.json()) as { data: Order[] }).data;
    } catch {
        error.value = 'Customer orders are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
defineOptions({ layout: { breadcrumbs: [{ title: 'Customer orders', href: ordersIndex() }] } });
</script>
<template>
    <Head title="Customer orders" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Customer orders"
            description="Paid orders containing your products"
        />
        <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <div v-if="isLoading" class="animate-pulse space-y-2">
                <div class="bg-muted h-10 rounded" />
                <div class="bg-muted h-10 rounded" />
                <div class="bg-muted h-10 rounded" />
            </div>
            <p v-else-if="error" class="text-sm text-amber-600">{{ error }}</p>
            <p v-else-if="orders.length === 0" class="text-muted-foreground text-sm">
                No customer orders yet.
            </p>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Order</th>
                            <th class="px-3 py-2 font-medium">Order status</th>
                            <th class="px-3 py-2 font-medium">Tracking status</th>
                            <th class="px-3 py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders"
                            :key="order.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link :href="show(order.order_number)" class="font-medium hover:underline">
                                    {{ order.order_number }}
                                </Link>
                                <p class="text-muted-foreground text-xs">{{ order.created_at }}</p>
                            </td>
                            <td class="px-3 py-2">{{ order.status }}</td>
                            <td class="px-3 py-2">{{ order.tracking_status ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">
                                <Link :href="show(order.order_number)" class="text-muted-foreground hover:underline">
                                    Detail
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
