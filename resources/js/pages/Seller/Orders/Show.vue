<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index } from '@/routes/seller/orders';
import { formatPrice } from '@/services/priceFormatter';

type OrderItem = { id: number; product_name: string; price: string | number; quantity: number; subtotal: string | number };
type SellerTracking = { courier?: string | null; waybill_number?: string | null; tracking_url?: string | null; tracking_status?: string | null };
type SellerOrder = {
    tracking?: SellerTracking | null;
    subtotal: string | number; shipping_fee: string | number; total: string | number; currency_code: string; items: OrderItem[];
    status?: string | null; created_at?: string | null; customer_name: string; customer_address: string; customer_state: string;
    customer_city?: string | null; customer_post_code: string; shipping_address?: string | null; shipping_state?: string | null;
    shipping_city?: string | null; shipping_post_code?: string | null; shipping_method?: string | null;
};

const props = defineProps<{ orderNumber: string }>();
const order = ref<SellerOrder | null>(null);
const courier = ref(''); const waybillNumber = ref(''); const trackingUrl = ref(''); const trackingStatus = ref('packed');
const message = ref('');
const customerLocation = computed(() => order.value ? [order.value.customer_city, order.value.customer_state, order.value.customer_post_code].filter(Boolean).join(', ') || '-' : '-');
const shippingLocation = computed(() => order.value ? [order.value.shipping_city, order.value.shipping_state, order.value.shipping_post_code].filter(Boolean).join(', ') || '-' : '-');
function displayMoney(value: string | number): string { const amount = typeof value === 'number' ? value : Number(value); return Number.isFinite(amount) ? formatPrice(amount) : String(value); }
function displayText(value: string | null | undefined): string { return value || '-'; }

onMounted(async () => {
    const response = await fetch(`/api/v1/seller/orders/${props.orderNumber}`, { headers: { Accept: 'application/json' } });
    if (!response.ok) { message.value = 'Order unavailable.'; return; }
    order.value = (await response.json() as { data: SellerOrder }).data;
    courier.value = order.value.tracking?.courier ?? ''; waybillNumber.value = order.value.tracking?.waybill_number ?? ''; trackingUrl.value = order.value.tracking?.tracking_url ?? ''; trackingStatus.value = order.value.tracking?.tracking_status ?? 'packed';
});
async function save(): Promise<void> { const response = await fetch(`/api/v1/seller/orders/${props.orderNumber}`, { method: 'PUT', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '' }, body: JSON.stringify({ courier: courier.value || null, waybill_number: waybillNumber.value || null, tracking_url: trackingUrl.value || null, tracking_status: trackingStatus.value }) }); message.value = response.ok ? 'Tracking saved.' : 'Tracking save failed.'; }
defineOptions({ layout: { breadcrumbs: [{ title: 'Customer orders', href: index() }] } });
</script>
<template>
    <Head :title="`Order ${orderNumber}`" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link :href="index()" class="text-muted-foreground w-fit text-sm hover:underline">← Back to Customer orders</Link>
        <Heading variant="small" :title="orderNumber" :description="`Placed ${displayText(order?.created_at)}`" />
        <div class="flex flex-wrap items-center gap-2"><Badge variant="secondary">{{ displayText(order?.status) }}</Badge><Badge variant="outline">Seller order</Badge></div>
        <div v-if="order" class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <h3 class="mb-1 text-base font-medium">Items ({{ order.items.length }})</h3><p class="text-muted-foreground mb-4 text-sm">Products assigned to this seller.</p>
            <p v-if="order.items.length === 0" class="text-muted-foreground text-sm">No items in this order.</p>
            <div v-else class="overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm"><thead><tr class="text-muted-foreground border-b font-medium"><th class="px-3 py-2">Product</th><th class="px-3 py-2 text-right">Price</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2 text-right">Subtotal</th></tr></thead><tbody><tr v-for="item in order.items" :key="item.id" class="hover:bg-muted/50 border-b transition-colors last:border-0"><td class="px-3 py-2 font-medium">{{ item.product_name }}</td><td class="px-3 py-2 text-right whitespace-nowrap">{{ displayMoney(item.price) }}</td><td class="px-3 py-2 text-right whitespace-nowrap">{{ item.quantity }}</td><td class="px-3 py-2 text-right whitespace-nowrap">{{ displayMoney(item.subtotal) }}</td></tr></tbody></table></div>
        </div>
        <div v-if="order" class="grid gap-4 lg:grid-cols-2"><div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"><h3 class="mb-3 text-base font-medium">Billing customer</h3><table class="w-full text-left text-sm"><tbody><tr class="border-b"><th class="text-muted-foreground w-32 px-3 py-2 text-left font-medium">Name</th><td class="px-3 py-2">{{ order.customer_name }}</td></tr><tr class="border-b"><th class="text-muted-foreground w-32 px-3 py-2 text-left font-medium">Address</th><td class="px-3 py-2">{{ order.customer_address }}</td></tr><tr><th class="text-muted-foreground w-32 px-3 py-2 text-left font-medium">City / State</th><td class="px-3 py-2">{{ customerLocation }}</td></tr></tbody></table></div><div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"><h3 class="mb-3 text-base font-medium">Delivery address</h3><table class="w-full text-left text-sm"><tbody><tr class="border-b"><th class="text-muted-foreground w-32 px-3 py-2 text-left font-medium">Address</th><td class="px-3 py-2">{{ order.shipping_address || 'Same as billing' }}</td></tr><tr class="border-b"><th class="text-muted-foreground w-32 px-3 py-2 text-left font-medium">City / State</th><td class="px-3 py-2">{{ shippingLocation }}</td></tr><tr><th class="text-muted-foreground w-32 px-3 py-2 text-left font-medium">Shipping</th><td class="px-3 py-2">{{ displayText(order.shipping_method) }}</td></tr></tbody></table></div></div>
        <div class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"><h3 class="mb-3 text-base font-medium">Courier & tracking</h3><div class="grid gap-3 sm:grid-cols-2"><input v-model="courier" placeholder="Courier provider" class="rounded border px-3 py-2 text-sm" /><input v-model="waybillNumber" placeholder="Waybill number" class="rounded border px-3 py-2 text-sm" /><input v-model="trackingUrl" placeholder="Tracking URL" type="url" class="rounded border px-3 py-2 text-sm" /><select v-model="trackingStatus" class="rounded border px-3 py-2 text-sm"><option value="packed">packed</option><option value="shipped">shipped</option></select></div><button type="button" class="mt-3 rounded bg-primary px-4 py-2 text-sm text-primary-foreground" @click="save">Save tracking</button><p v-if="message" class="mt-2 text-sm text-muted-foreground">{{ message }}</p></div>
    </div>
</template>
