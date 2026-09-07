<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { HomeCategoryItem } from '@/types/marketplace';

const props = defineProps<{
    categories: HomeCategoryItem[];
    loading?: boolean;
}>();

const tiles = computed(() =>
    props.categories.map((category) => ({
        id: category.id,
        name: category.name,
        href: `/products?category=${category.slug}`,
    })),
);
</script>

<template>
    <section
        aria-label="Categories"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center justify-between border-b border-[var(--border-soft)] pb-3"
        >
            <h2 class="text-base font-semibold text-[var(--text-primary)]">
                Categories
            </h2>
            <Link
                href="/products"
                class="text-xs text-[var(--text-secondary)] hover:underline"
            >
                Lihat Semua &gt;
            </Link>
        </div>
        <div
            v-if="loading"
            class="mt-3 grid grid-cols-5 gap-px sm:grid-cols-8 lg:grid-cols-10"
        >
            <div
                v-for="n in 10"
                :key="n"
                class="h-[140px] animate-pulse bg-[var(--bg-muted)]"
            />
        </div>
        <div
            v-else-if="tiles.length === 0"
            class="mt-3 rounded-sm bg-[var(--bg-muted)] p-6 text-center text-xs text-[var(--text-muted)]"
        >
            Categories will appear here once created.
        </div>
        <div
            v-else
            class="mt-3 grid grid-cols-5 gap-px overflow-hidden rounded-sm border border-[var(--border-soft)] sm:grid-cols-8 lg:grid-cols-10"
        >
            <Link
                v-for="tile in tiles"
                :key="tile.id"
                :href="tile.href"
                class="flex min-h-[140px] flex-col items-center justify-center gap-2 border-[var(--border-soft)] bg-white p-3 transition hover:shadow-[var(--shadow-card)]"
            >
                <span
                    class="flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold text-white"
                    style="background-color: var(--brand-primary)"
                    aria-hidden="true"
                >
                    {{ tile.name.charAt(0) }}
                </span>
                <span class="line-clamp-2 text-center text-[12px]">
                    {{ tile.name }}
                </span>
            </Link>
        </div>
    </section>
</template>
