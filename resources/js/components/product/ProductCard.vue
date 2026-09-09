<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Price from '@/components/product/Price.vue';
import type { ProductCardData } from '@/types/marketplace';

defineProps<{ product: ProductCardData }>();
</script>

<template>
    <Link
        :href="`/products/${product.slug}`"
        class="group flex flex-col overflow-hidden rounded-sm border border-[var(--border-default)] bg-[var(--bg-surface)] transition duration-150 hover:-translate-y-0.5 hover:border-[var(--brand-primary)] hover:shadow-[var(--shadow-hover)]"
    >
        <div class="relative aspect-square bg-white overflow-hidden">
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-xs text-[var(--text-muted)]"
            >
                No image
            </div>
            <span
                v-if="product.discountPercent !== undefined"
                class="absolute top-0 right-0 rounded-bl-sm bg-[var(--accent-red)] px-1.5 py-0.5 text-[10px] font-bold text-white"
            >
                -{{ product.discountPercent }}%
            </span>
            <span
                v-if="product.freeShipping"
                class="absolute bottom-1 left-1 rounded-sm bg-[var(--accent-cyan)] px-1.5 py-0.5 text-[10px] font-semibold text-white"
            >
                Free shipping
            </span>
        </div>
        <div class="flex flex-1 flex-col p-2">
            <p
                class="line-clamp-2 min-h-9 text-[12px] leading-[1.4] text-[var(--text-primary)]"
            >
                {{ product.name }}
            </p>
            <div class="mt-1">
                <Price
                    :amount="product.price"
                    :original-amount="product.originalPrice ?? null"
                />
            </div>
            <p
                v-if="
                    product.rating !== undefined ||
                    product.soldCount !== undefined
                "
                class="mt-1 text-[11px] text-[var(--text-muted)]"
            >
                <span v-if="product.rating !== undefined">
                    ★ {{ product.rating.toFixed(1) }}
                </span>
                <span
                    v-if="
                        product.rating !== undefined &&
                        product.soldCount !== undefined
                    "
                >
                    ·
                </span>
                <span v-if="product.soldCount !== undefined">
                    {{ product.soldCount }} sold
                </span>
            </p>
            <p
                v-if="product.location || product.stockLabel"
                class="mt-auto pt-1 text-[11px] text-[var(--text-muted)]"
            >
                {{ product.location ?? product.stockLabel }}
            </p>
        </div>
    </Link>
</template>
