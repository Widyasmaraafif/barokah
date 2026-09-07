<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/payments';

type AdminPaymentDetail = {
    id: number;
    payment_method: string;
    payment_gateway: string;
    status: string;
    amount: string | number;
};

defineProps<{
    payment: AdminPaymentDetail;
}>();

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
</script>

<template>
    <Head title="PayNet transaction detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            :title="`${payment.payment_method} · ${payment.amount}`"
            :description="`${payment.payment_gateway} · ${payment.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <p class="text-muted-foreground text-sm">
                Callback payloads stay server-only. Refunds are TBC.
            </p>
        </div>
    </div>
</template>
