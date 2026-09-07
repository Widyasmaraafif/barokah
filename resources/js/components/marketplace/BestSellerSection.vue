<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { HomeProductItem } from '@/types/marketplace';
import { toProductCardData } from '@/types/marketplace';

const props = defineProps<{
    products: HomeProductItem[];
    loading?: boolean;
}>();

const cards = computed(() => props.products.map(toProductCardData));
</script>

<template>
    <section
        aria-label="Best sellers preview"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center justify-between border-b border-[var(--border-soft)] pb-3"
        >
            <h2 class="text-base font-semibold text-[var(--text-primary)]">
                Produk Terlaris
            </h2>
            <Link
                href="/products"
                class="text-xs text-[var(--text-secondary)] hover:underline"
            >
                Lihat Semua &gt;
            </Link>
        </div>
        <p class="mt-2 text-[11px] text-[var(--text-muted)]">
            Ranking preview only. Best-seller ranking backend is TBC (spec §24
            item 22).
        </p>
        <div v-if="loading" class="mt-3 flex gap-2 overflow-hidden">
            <div
                v-for="n in 6"
                :key="n"
                class="h-[120px] w-[220px] shrink-0 animate-pulse rounded-sm bg-[var(--bg-muted)]"
            />
        </div>
        <p
            v-else-if="cards.length === 0"
            class="mt-3 rounded-sm bg-[var(--bg-muted)] p-6 text-center text-xs text-[var(--text-muted)]"
        >
            Best sellers will appear here once sales data is available.
        </p>
        <div v-else class="mt-3 flex gap-2 overflow-x-auto pb-1">
            <article
                v-for="(card, index) in cards.slice(0, 8)"
                :key="card.id"
                class="flex w-[220px] shrink-0 items-center gap-2 rounded-sm border border-[var(--border-soft)] p-2"
            >
                <span
                    class="text-2xl font-bold"
                    style="color: var(--brand-primary)"
                >
                    {{ index + 1 }}
                </span>
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-sm bg-white">
                    <img
                        v-if="card.image"
                        :src="card.image"
                        :alt="card.name"
                        loading="lazy"
                        class="h-full w-full object-cover"
                    />
                </div>
                <p class="line-clamp-2 text-[12px]">{{ card.name }}</p>
            </article>
        </div>
    </section>
</template>
