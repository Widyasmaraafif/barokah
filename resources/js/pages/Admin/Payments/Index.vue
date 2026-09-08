<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index, show } from '@/routes/admin/payments';
import { fetchAdminList } from '../useAdminList';

type AdminPayment = {
    id: number;
    payment_method: string;
    status: string;
    amount: string | number;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'PayNet transactions',
                href: index(),
            },
        ],
    },
});

const payments = ref<AdminPayment[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        payments.value = await fetchAdminList<AdminPayment>('/api/v1/admin/payments');
    } catch {
        error.value = 'PayNet transactions are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="PayNet transactions" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="PayNet transactions"
            description="List and detail only. Refunds are TBC."
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
                v-else-if="payments.length === 0"
                class="text-muted-foreground text-sm"
            >
                No PayNet transactions yet.
            </p>

            <ul v-else class="divide-y">
                <li
                    v-for="payment in payments"
                    :key="payment.id"
                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                >
                    <Link
                        :href="show(payment.id)"
                        class="font-medium hover:underline"
                    >
                        {{ payment.payment_method }} · {{ payment.amount }}
                    </Link>
                    <p class="text-muted-foreground text-sm">{{ payment.status }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>
