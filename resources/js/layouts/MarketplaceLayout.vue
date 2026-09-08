<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import HeroPromo from '@/components/marketplace/HeroPromo.vue';
import MainHeader from '@/components/marketplace/MainHeader.vue';
import MarketplaceFooter from '@/components/marketplace/MarketplaceFooter.vue';
import MobileBottomNav from '@/components/marketplace/MobileBottomNav.vue';
import UtilityBar from '@/components/marketplace/UtilityBar.vue';
import { useSettingsStore } from '@/stores/settings';

const { getSettingValue, loadSettings } = useSettingsStore();

void loadSettings();

const slots = defineSlots<{
    default?: () => unknown;
    mobileNav?: () => unknown;
}>();

const props = withDefaults(
    defineProps<{
        showHero?: boolean;
    }>(),
    { showHero: false },
);

const showDefaultNav = computed(() => !slots.mobileNav);

const siteName = computed(() =>
    getSettingValue<string>('branding.site_name', 'Barokah'),
);

const pageTitle = computed(() => `${siteName.value} Marketplace`);
</script>

<template>
    <div
        class="min-h-screen font-[Arial,Helvetica,'Noto_Sans',sans-serif] text-[var(--text-primary)]"
        style="background-color: var(--bg-page)"
    >
        <Head :title="pageTitle">
            <meta
                name="description"
                content="Barokah multi-seller marketplace for physical goods in Malaysia."
            />
        </Head>

        <UtilityBar />
        <MainHeader />
        <main
            class="mx-auto w-full px-4 pb-20 md:pb-8"
            style="max-width: var(--container-max)"
        >
            <HeroPromo v-if="props.showHero" />
            <slot />
        </main>
        <MarketplaceFooter />

        <slot name="mobileNav" />
        <MobileBottomNav v-if="showDefaultNav" />
    </div>
</template>
