<script setup lang="ts">
import BestSellerSection from '@/components/marketplace/BestSellerSection.vue';
import CategorySection from '@/components/marketplace/CategorySection.vue';
import FlashSaleSection from '@/components/marketplace/FlashSaleSection.vue';
import LiveSection from '@/components/marketplace/LiveSection.vue';
import QuickServices from '@/components/marketplace/QuickServices.vue';
import RecommendationSection from '@/components/marketplace/RecommendationSection.vue';
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue';
import { useSettingsStore } from '@/stores/settings';
import type { HomeCategoryItem, HomeProductItem } from '@/types/marketplace';

const props = defineProps<{
    categories: HomeCategoryItem[] | { data: HomeCategoryItem[] };
    latestProducts: HomeProductItem[] | { data: HomeProductItem[] };
    flashSaleProducts: HomeProductItem[] | { data: HomeProductItem[] };
    bestSellerProducts: HomeProductItem[] | { data: HomeProductItem[] };
}>();

const { loadSettings } = useSettingsStore();

void loadSettings();

function unwrap<T>(value: T[] | { data: T[] }): T[] {
    return Array.isArray(value) ? value : (value?.data ?? []);
}
</script>

<template>
    <MarketplaceLayout>
        <QuickServices />
        <FlashSaleSection :products="unwrap(flashSaleProducts)" />
        <BestSellerSection :products="unwrap(bestSellerProducts)" />
        <LiveSection />
        <CategorySection :categories="unwrap(categories)" />
        <RecommendationSection :products="unwrap(latestProducts)" />
    </MarketplaceLayout>
</template>
