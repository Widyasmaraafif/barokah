<script setup lang="ts">
import type { Seller } from '@/types/marketplace';

defineProps<{
    sellers: Seller[];
}>();
</script>

<template>
    <section
        aria-label="Featured sellers"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center justify-between border-b border-[var(--border-soft)] pb-3"
        >
            <h2 class="text-base font-semibold text-[var(--text-primary)]">
                Seller
            </h2>
            <span class="text-xs text-[var(--text-secondary)]">
                Seller aktif
            </span>
        </div>

        <p v-if="sellers.length === 0" class="mt-3 text-sm text-[var(--text-muted)]">
            Belum ada seller aktif.
        </p>

        <div v-else class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article
                v-for="seller in sellers"
                :key="seller.id"
                class="flex items-center gap-3 rounded-sm border border-[var(--border-soft)] p-3"
            >
                <img
                    v-if="seller.profile_photo_url"
                    :src="seller.profile_photo_url"
                    :alt="seller.store_name"
                    class="size-12 shrink-0 rounded-full object-cover"
                />
                <div
                    v-else
                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[var(--accent-navy)] text-sm font-semibold text-white"
                    aria-hidden="true"
                >
                    {{ seller.store_name.charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                    <h3 class="truncate text-sm font-medium text-[var(--text-primary)]">
                        {{ seller.store_name }}
                    </h3>
                    <p class="truncate text-xs text-[var(--text-muted)]">
                        {{ seller.city || seller.state || 'Marketplace seller' }}
                    </p>
                </div>
            </article>
        </div>
    </section>
</template>
