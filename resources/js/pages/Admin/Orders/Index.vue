<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index, show } from '@/routes/admin/orders';
import { fetchAdminList } from '../useAdminList';

type AdminOrderListItem = {
    id: number;
    order_number: string;
    status: string;
    total: string | number;
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

            <ul v-else class="divide-y">
                <li
                    v-for="order in orders"
                    :key="order.id"
                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                >
                    <div>
                        <Link
                            :href="show(order.order_number)"
                            class="font-medium hover:underline"
                        >
                            {{ order.order_number }}
                        </Link>
                        <p class="text-muted-foreground text-sm">
                            {{ order.created_at }}
                        </p>
                    </div>
                    <p class="text-muted-foreground text-sm">{{ order.status }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>
