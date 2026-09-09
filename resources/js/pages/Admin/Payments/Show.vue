<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/payments';

type AdminPaymentDetail = {
    id: number;
    payment_method: string;
    payment_gateway: string;
    status: string;
    amount: string | number;
};

const props = defineProps<{
    payment: AdminPaymentDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Payment transactions',
                href: index(),
            },
        ],
    },
});

const isManual = computed(() => props.payment.payment_method === 'bank_transfer' || props.payment.payment_method === 'qr_code');
const isPending = computed(() => props.payment.status === 'pending');
const isVerifying = ref(false);
const verifyError = ref<string | null>(null);

async function verify(status: 'paid' | 'failed'): Promise<void> {
    isVerifying.value = true;
    verifyError.value = null;

    try {
        const response = await fetch(`/api/v1/admin/payments/${props.payment.id}/verify`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '',
            },
            body: JSON.stringify({ status }),
        });

        if (!response.ok) {
            const payload = (await response.json().catch(() => null)) as { message?: string } | null;
            verifyError.value = payload?.message ?? 'Verification failed.';
            return;
        }

        router.reload();
    } catch {
        verifyError.value = 'Verification failed. Please try again.';
    } finally {
        isVerifying.value = false;
    }
}
</script>

<template>
    <Head title="Payment transaction detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Payment transactions
        </Link>
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

            <div v-if="isManual && isPending" class="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="isVerifying"
                    class="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                    @click="verify('paid')"
                >
                    {{ isVerifying ? 'Verifying…' : 'Mark paid' }}
                </button>
                <button
                    type="button"
                    :disabled="isVerifying"
                    class="rounded border px-4 py-2 text-sm font-medium disabled:opacity-50"
                    @click="verify('failed')"
                >
                    Mark failed
                </button>
            </div>
            <p v-if="verifyError" class="mt-2 text-sm text-red-600">{{ verifyError }}</p>
        </div>
    </div>
</template>
