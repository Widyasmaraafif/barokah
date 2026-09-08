<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, show } from '@/routes/admin/payments';
import { formatPrice } from '@/services/priceFormatter';
import { fetchAdminList } from '../useAdminList';

type AdminPayment = {
    id: number;
    order_number?: string | null;
    payment_method: string;
    payment_gateway?: string | null;
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

function displayAmount(amount: string | number): string {
    const value = typeof amount === 'number' ? amount : Number(amount);

    return Number.isFinite(value) ? formatPrice(value) : String(amount);
}

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

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Transaction</th>
                            <th class="px-3 py-2 font-medium">Order</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Amount
                            </th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="payment in payments"
                            :key="payment.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="show(payment.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ payment.payment_method }}
                                </Link>
                                <p class="text-muted-foreground text-xs">
                                    {{ payment.payment_gateway ?? '-' }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                {{ payment.order_number ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ displayAmount(payment.amount) }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge variant="secondary">
                                    {{ payment.status }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <Link
                                        :href="show(payment.id)"
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
