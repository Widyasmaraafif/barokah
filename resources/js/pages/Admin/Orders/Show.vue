<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index } from '@/routes/admin/orders';
import { formatPrice } from '@/services/priceFormatter';

type AdminOrderDetailItem = {
    id: number;
    product_name: string;
    product_slug: string;
    price: string | number;
    quantity: number;
    subtotal: string | number;
    seller?: string | null;
};

type AdminOrderDetailPayment = {
    payment_method: string;
    payment_gateway: string;
    status: string;
    amount: string | number;
    transaction_id?: string | null;
    paid_at?: string | null;
} | null;

type AdminOrderDetail = {
    id: number;
    order_number: string;
    status: string;
    currency_code: string;
    customer_name: string;
    customer_address: string;
    customer_state: string;
    customer_city?: string | null;
    customer_post_code: string;
    customer_phone: string;
    customer_email?: string | null;
    subtotal: string | number;
    shipping_fee: string | number;
    total: string | number;
    shipping_method: string;
    shipping_provider?: string | null;
    notes?: string | null;
    expired_at?: string | null;
    created_at?: string | null;
    payment: AdminOrderDetailPayment;
    items: AdminOrderDetailItem[];
};

const props = defineProps<{
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

function displayMoney(value: string | number): string {
    const amount = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(amount) ? formatPrice(amount) : String(value);
}

function displayText(value: string | null | undefined): string {
    return value === null || value === undefined || value === ''
        ? '-'
        : value;
}

const customerLocation = computed(() => {
    const parts = [
        props.order.customer_city,
        props.order.customer_state,
        props.order.customer_post_code,
    ].filter(
        (part): part is string => typeof part === 'string' && part !== '',
    );

    return parts.length > 0 ? parts.join(', ') : '-';
});
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Customer orders
        </Link>
        <Heading
            variant="small"
            :title="order.order_number"
            :description="`Placed ${displayText(order.created_at)}`"
        />

        <div class="flex flex-wrap items-center gap-2">
            <Badge variant="secondary">{{ order.status }}</Badge>
            <Badge v-if="order.payment" variant="outline">
                {{ order.payment.payment_method }} ·
                {{ order.payment.status }}
            </Badge>
            <Badge v-else variant="outline">No payment yet</Badge>
        </div>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <h3 class="mb-1 text-base font-medium">
                Items ({{ order.items.length }})
            </h3>
            <p class="text-muted-foreground mb-4 text-sm">
                Product snapshots saved at checkout time.
            </p>

            <p
                v-if="order.items.length === 0"
                class="text-muted-foreground text-sm"
            >
                No items in this order.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Product</th>
                            <th class="px-3 py-2 font-medium">Store</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Price
                            </th>
                            <th class="px-3 py-2 text-right font-medium">
                                Qty
                            </th>
                            <th class="px-3 py-2 text-right font-medium">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in order.items"
                            :key="item.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2 font-medium">
                                {{ item.product_name }}
                                <p class="text-muted-foreground text-xs font-normal">
                                    {{ item.product_slug }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                {{ displayText(item.seller) }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ displayMoney(item.price) }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ item.quantity }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ displayMoney(item.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t">
                            <td
                                colspan="4"
                                class="text-muted-foreground px-3 py-2 text-right"
                            >
                                Subtotal
                            </td>
                            <td
                                class="px-3 py-2 text-right whitespace-nowrap"
                            >
                                {{ displayMoney(order.subtotal) }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                colspan="4"
                                class="text-muted-foreground px-3 py-2 text-right"
                            >
                                Shipping ({{ order.shipping_method }})
                            </td>
                            <td
                                class="px-3 py-2 text-right whitespace-nowrap"
                            >
                                {{ displayMoney(order.shipping_fee) }}
                            </td>
                        </tr>
                        <tr class="font-semibold">
                            <td colspan="4" class="px-3 py-2 text-right">
                                Total ({{ order.currency_code }})
                            </td>
                            <td
                                class="px-3 py-2 text-right whitespace-nowrap"
                            >
                                {{ displayMoney(order.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <h3 class="mb-3 text-base font-medium">Customer</h3>
                <table class="w-full text-left text-sm">
                    <tbody>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Name
                            </th>
                            <td class="px-3 py-2">
                                {{ order.customer_name }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Phone
                            </th>
                            <td class="px-3 py-2">
                                {{ order.customer_phone }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Email
                            </th>
                            <td class="px-3 py-2">
                                {{ displayText(order.customer_email) }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Address
                            </th>
                            <td class="px-3 py-2">
                                {{ order.customer_address }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                City / State
                            </th>
                            <td class="px-3 py-2">
                                {{ customerLocation }}
                            </td>
                        </tr>
                        <tr v-if="order.notes" class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Notes
                            </th>
                            <td class="px-3 py-2">{{ order.notes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <h3 class="mb-3 text-base font-medium">Payment & shipping</h3>
                <table class="w-full text-left text-sm">
                    <tbody>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Method
                            </th>
                            <td class="px-3 py-2">
                                {{
                                    order.payment
                                        ? order.payment.payment_method
                                        : '-'
                                }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Gateway
                            </th>
                            <td class="px-3 py-2">
                                {{
                                    order.payment
                                        ? order.payment.payment_gateway
                                        : '-'
                                }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Pay status
                            </th>
                            <td class="px-3 py-2">
                                {{
                                    order.payment
                                        ? order.payment.status
                                        : 'No payment yet'
                                }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Amount
                            </th>
                            <td class="px-3 py-2">
                                {{
                                    order.payment
                                        ? displayMoney(order.payment.amount)
                                        : '-'
                                }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Transaction
                            </th>
                            <td class="px-3 py-2">
                                {{
                                    displayText(
                                        order.payment?.transaction_id,
                                    )
                                }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Paid at
                            </th>
                            <td class="px-3 py-2">
                                {{
                                    displayText(order.payment?.paid_at)
                                }}
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Shipping
                            </th>
                            <td class="px-3 py-2">
                                {{ order.shipping_method }}
                                <span
                                    v-if="order.shipping_provider"
                                    class="text-muted-foreground"
                                >
                                    · {{ order.shipping_provider }}
                                </span>
                            </td>
                        </tr>
                        <tr class="border-b last:border-0">
                            <th
                                class="text-muted-foreground w-32 px-3 py-2 align-top font-medium"
                            >
                                Expires
                            </th>
                            <td class="px-3 py-2">
                                {{ displayText(order.expired_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
