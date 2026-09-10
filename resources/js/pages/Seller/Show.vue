<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { show as productShow } from '@/routes/products';
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue';
import type { HomeProductItem, HomeSellerItem } from '@/types/marketplace';
import { useSettingsStore } from '@/stores/settings';

const props = defineProps<{
    seller: HomeSellerItem | { data: HomeSellerItem };
    products: HomeProductItem[] | { data: HomeProductItem[] };
}>();

const { formatAmount, loadSettings } = useSettingsStore();
void loadSettings();

function unwrap<T>(value: T[] | { data: T[] }): T[] {
    return Array.isArray(value) ? value : value.data;
}

const seller = 'data' in props.seller ? props.seller.data : props.seller;
</script>

<template>
    <Head :title="seller.store_name" />
    <MarketplaceLayout>
        <div class="mx-auto w-full max-w-[1200px] px-4 py-6 pb-24 md:pb-6">
            <section class="rounded-sm border border-[var(--border-default)] bg-white p-5 shadow-[var(--shadow-card)]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <img v-if="seller.profile_photo_url" :src="seller.profile_photo_url" :alt="seller.store_name" class="size-24 rounded-full object-cover" />
                    <div v-else class="flex size-24 items-center justify-center rounded-full bg-[var(--accent-navy)] text-2xl font-semibold text-white">{{ seller.store_name.charAt(0).toUpperCase() }}</div>
                    <div>
                        <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ seller.store_name }}</h1>
                        <p class="mt-1 text-sm text-[var(--text-secondary)]">{{ seller.city || seller.state || seller.store_location || 'Marketplace seller' }}</p>
                        <p v-if="seller.description" class="mt-3 max-w-2xl text-sm text-[var(--text-secondary)]">{{ seller.description }}</p>
                        <p v-if="seller.phone || seller.whatsapp" class="mt-2 text-sm text-[var(--text-muted)]">{{ seller.phone || seller.whatsapp }}</p>
                    </div>
                </div>
            </section>

            <section class="mt-6">
                <h2 class="text-lg font-semibold text-[var(--text-primary)]">Produk seller</h2>
                <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-6">
                    <Link v-for="product in unwrap(products)" :key="product.id" :href="productShow.url(product.slug)" class="overflow-hidden rounded-sm border border-[var(--border-soft)] bg-white hover:border-[var(--brand-primary)]">
                        <div class="aspect-square bg-[var(--bg-muted)]">
                            <img v-if="product.primary_image" :src="product.primary_image" :alt="product.name" class="h-full w-full object-cover" />
                        </div>
                        <div class="p-3">
                            <h3 class="truncate text-sm font-medium text-[var(--text-primary)]">{{ product.name }}</h3>
                            <p class="mt-1 text-sm font-semibold text-[var(--brand-primary)]">{{ formatAmount(Number(product.price)) }}</p>
                        </div>
                    </Link>
                </div>
                <p v-if="unwrap(products).length === 0" class="mt-3 text-sm text-[var(--text-muted)]">Belum ada produk aktif.</p>
            </section>
        </div>
    </MarketplaceLayout>
</template>
