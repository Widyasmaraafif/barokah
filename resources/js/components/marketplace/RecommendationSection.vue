<script setup lang="ts">
import ProductCard from '@/components/product/ProductCard.vue';
import type { HomeProductItem, ProductCardData } from '@/types/marketplace';
import { toProductCardData } from '@/types/marketplace';
import { computed } from 'vue';

const props = defineProps<{
    products: HomeProductItem[];
    loading?: boolean;
}>();

const cards = computed<ProductCardData[]>(() =>
    props.products.map(toProductCardData),
);
</script>

<template>
    <section
        aria-label="Recommendations"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center justify-between border-b border-[var(--border-soft)] pb-3"
        >
            <h2 class="text-base font-semibold text-[var(--text-primary)]">
                Recommendations
            </h2>
        </div>
        <div
            v-if="loading"
            class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
        >
            <div
                v-for="n in 12"
                :key="n"
                class="overflow-hidden rounded-sm border border-[var(--border-soft)]"
            >
                <div class="aspect-square animate-pulse bg-[var(--bg-muted)]" />
                <div class="space-y-2 p-2">
                    <div
                        class="h-3 animate-pulse rounded bg-[var(--bg-muted)]"
                    />
                    <div
                        class="h-4 w-2/3 animate-pulse rounded bg-[var(--bg-muted)]"
                    />
                </div>
            </div>
        </div>
        <p
            v-else-if="cards.length === 0"
            class="mt-3 rounded-sm bg-[var(--bg-muted)] p-8 text-center text-sm text-[var(--text-muted)]"
        >
            No recommendations yet. New products will appear here.
        </p>
        <div
            v-else
            class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
        >
            <ProductCard
                v-for="card in cards"
                :key="card.id"
                :product="card"
            />
        </div>
    </section>
</template>
