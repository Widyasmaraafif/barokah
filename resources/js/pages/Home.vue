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

defineProps<{
    categories: HomeCategoryItem[];
    latestProducts: HomeProductItem[];
    flashSaleProducts: HomeProductItem[];
    bestSellerProducts: HomeProductItem[];
}>();

const { loadSettings } = useSettingsStore();

void loadSettings();
</script>

<template>
    <MarketplaceLayout>
        <QuickServices />
        <FlashSaleSection :products="flashSaleProducts" />
        <BestSellerSection :products="bestSellerProducts" />
        <LiveSection />
        <CategorySection :categories="categories" />
        <RecommendationSection :products="latestProducts" />
    </MarketplaceLayout>
</template>
