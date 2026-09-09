<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue';
import { useCartStore } from '@/stores/cart';
import { useCheckoutStore } from '@/stores/checkout';
import { useSettingsStore } from '@/stores/settings';

const { formatAmount } = useSettingsStore();
const { state, subtotal, update, remove } = useCartStore();
const { startBuy } = useCheckoutStore();

function checkout(item: { productId: number; slug: string; quantity: number }): void {
    startBuy(item.productId, item.slug, item.quantity);
    window.location.href = `/checkout/${item.slug}`;
}

function checkoutCart(): void {
    window.location.href = '/checkout/cart';
}
</script>

<template>
    <Head title="Shopping cart" />
    <MarketplaceLayout>
        <div class="mx-auto w-full max-w-[1200px] px-4 py-6 pb-24">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Shopping cart</h1>
            <p v-if="state.items.length === 0" class="mt-6 text-sm text-[var(--text-muted)]">
                Your cart is empty.
            </p>
            <div v-else class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-3">
                    <article
                        v-for="item in state.items"
                        :key="item.productId"
                        class="flex gap-4 rounded-sm border border-[var(--border-default)] bg-white p-4"
                    >
                        <img v-if="item.image" :src="item.image" :alt="item.name" class="size-24 rounded-sm object-cover" />
                        <div class="min-w-0 flex-1">
                            <Link :href="`/products/${item.slug}`" class="font-medium hover:underline">{{ item.name }}</Link>
                            <p class="mt-1 text-sm text-[var(--brand-primary)]">{{ formatAmount(item.price) }}</p>
                            <div class="mt-3 flex items-center gap-3">
                                <input
                                    :value="item.quantity"
                                    type="number"
                                    min="1"
                                    :max="item.stock"
                                    class="h-9 w-20 rounded-sm border border-[var(--border-default)] px-2 text-sm"
                                    @change="update(item.productId, Number(($event.target as HTMLInputElement).value))"
                                />
                                <button type="button" class="text-sm text-red-600 hover:underline" @click="remove(item.productId)">Remove</button>
                                <button type="button" class="text-sm font-medium text-[var(--brand-primary)] hover:underline" @click="checkout(item)">Buy now</button>
                            </div>
                        </div>
                        <p class="text-sm font-semibold">{{ formatAmount(item.price * item.quantity) }}</p>
                    </article>
                </div>
                <aside class="h-fit rounded-sm border border-[var(--border-default)] bg-white p-4">
                    <h2 class="font-semibold">Summary</h2>
                    <div class="mt-4 flex justify-between text-sm"><span>Subtotal</span><strong>{{ formatAmount(subtotal) }}</strong></div>
                    <button
                        type="button"
                        class="mt-5 flex h-11 w-full items-center justify-center rounded-sm bg-[var(--brand-primary)] px-4 font-semibold text-white"
                        @click="checkoutCart"
                    >
                        Proceed to checkout
                    </button>
                </aside>
            </div>
        </div>
    </MarketplaceLayout>
</template>
