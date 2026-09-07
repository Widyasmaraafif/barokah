<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Price from '@/components/product/Price.vue';
import { useSettingsStore } from '@/stores/settings';
import type { HomeProductItem } from '@/types/marketplace';
import { toProductCardData } from '@/types/marketplace';

const props = defineProps<{
    products: HomeProductItem[];
    loading?: boolean;
    title?: string;
}>();

const { formatAmount } = useSettingsStore();

const cards = computed(() => props.products.map(toProductCardData));

function dealPrice(index: number, price: number): string {
    const discounted = price * (index === 0 ? 0.82 : 0.88);
    return formatAmount(discounted);
}
</script>

<template>
    <section
        aria-label="Flash sale preview"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center justify-between gap-2 border-b border-[var(--border-soft)] pb-3"
        >
            <h2
                class="text-base font-semibold"
                style="color: var(--brand-primary)"
            >
                {{ title ?? 'Flash Sale' }}
            </h2>
            <Link
                href="/products"
                class="text-xs text-[var(--text-secondary)] hover:underline"
            >
                Lihat Semua &gt;
            </Link>
        </div>
        <p class="mt-2 text-[11px] text-[var(--text-muted)]">
            UI preview only. Promo engine is TBC — no discounts are applied at
            checkout (spec §24 item 21).
        </p>
        <div v-if="loading" class="mt-3 flex gap-2 overflow-hidden">
            <div
                v-for="n in 6"
                :key="n"
                class="h-[190px] w-[180px] shrink-0 animate-pulse rounded-sm bg-[var(--bg-muted)]"
            />
        </div>
        <p
            v-else-if="cards.length === 0"
            class="mt-3 rounded-sm bg-[var(--bg-muted)] p-6 text-center text-xs text-[var(--text-muted)]"
        >
            Flash deals will appear here when promotions are configured.
        </p>
        <div v-else class="mt-3 flex gap-2 overflow-x-auto pb-1">
            <article
                v-for="(card, index) in cards.slice(0, 8)"
                :key="card.id"
                class="w-[180px] shrink-0 overflow-hidden rounded-sm border border-[var(--border-soft)] md:w-[190px]"
            >
                <div class="relative aspect-square bg-white">
                    <img
                        v-if="card.image"
                        :src="card.image"
                        :alt="card.name"
                        loading="lazy"
                        class="h-full w-full object-cover"
                    />
                    <span
                        class="absolute top-0 right-0 bg-[var(--accent-red)] px-1.5 py-0.5 text-[10px] font-bold text-white"
                    >
                        -{{ index === 0 ? 18 : 12 }}%
                    </span>
                </div>
                <div class="p-2">
                    <p
                        class="text-sm font-semibold"
                        style="color: var(--brand-primary)"
                    >
                        {{ dealPrice(index, card.price) }}
                    </p>
                    <div
                        class="mt-1 h-3 overflow-hidden rounded-full bg-[var(--brand-primary-soft)]"
                    >
                        <div
                            class="h-full rounded-full"
                            style="width: 62%; background-color: var(--brand-primary)"
                        />
                    </div>
                    <p class="mt-1 text-[11px] text-[var(--text-muted)]">
                        Selling fast · preview only
                    </p>
                    <p class="mt-1 hidden">
                        <Price :amount="card.price" />
                    </p>
                </div>
            </article>
        </div>
    </section>
</template>
