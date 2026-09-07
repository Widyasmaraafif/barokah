<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useSettingsStore } from '@/stores/settings';

type ConfirmationItem = {
    product_name: string;
    quantity: number;
    price: string | number;
    subtotal: string | number;
};

type ConfirmationOrder = {
    order_number: string;
    status: string;
    payment_status: string | null;
    currency_code: string;
    customer_name: string;
    subtotal: string | number;
    shipping_fee: string | number;
    total: string | number;
    shipping_method: string;
    expired_at: string | null;
    created_at: string | null;
    items: ConfirmationItem[];
};

const props = defineProps<{ order: ConfirmationOrder }>();

const { formatAmount, loadSettings } = useSettingsStore();

void loadSettings();

const paymentStatus = ref<string | null>(props.order.payment_status);
let pollTimer: ReturnType<typeof setInterval> | null = null;

async function pollPayment(): Promise<void> {
    try {
        const response = await fetch(`/api/v1/orders/${props.order.order_number}/payment`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        const payload = (await response.json()) as {
            data?: { status?: string };
        };

        const status = payload.data?.status ?? null;
        paymentStatus.value = status;

        if (status !== 'pending' && pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    } catch {
        // Keep polling; PayNet callbacks may still arrive.
    }
}

onMounted(() => {
    if (paymentStatus.value === 'pending') {
        pollTimer = setInterval(() => void pollPayment(), 3000);
    }
});

onBeforeUnmount(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="mx-auto w-full max-w-[1200px] px-4 py-6">
        <div class="rounded border bg-white p-6">
            <h1 class="text-xl font-semibold">Order Confirmation</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Order {{ order.order_number }} is {{ order.status }}.
            </p>
            <p v-if="paymentStatus" class="mt-1 text-sm text-muted-foreground">
                Payment is {{ paymentStatus }}.
                <span v-if="paymentStatus === 'pending'">Check again shortly — PayNet confirmation may still arrive.</span>
            </p>

            <dl class="mt-4 space-y-1 text-sm">
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Customer</dt>
                    <dd>{{ order.customer_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Subtotal</dt>
                    <dd>{{ formatAmount(Number(order.subtotal)) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Shipping ({{ order.shipping_method }})</dt>
                    <dd>{{ formatAmount(Number(order.shipping_fee)) }}</dd>
                </div>
                <div class="flex justify-between font-semibold">
                    <dt>Total</dt>
                    <dd>{{ formatAmount(Number(order.total)) }}</dd>
                </div>
            </dl>

            <ul class="mt-4 divide-y rounded border">
                <li
                    v-for="(item, index) in order.items"
                    :key="index"
                    class="flex justify-between p-3 text-sm"
                >
                    <span>{{ item.product_name }} × {{ item.quantity }}</span>
                    <span>{{ formatAmount(Number(item.subtotal)) }}</span>
                </li>
            </ul>

            <p v-if="order.expired_at" class="mt-3 text-xs text-muted-foreground">
                Complete payment before {{ order.expired_at }}.
            </p>

            <Link
                href="/products"
                class="mt-6 inline-block h-11 rounded bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white"
            >
                Continue shopping
            </Link>
        </div>
    </div>
</template>
