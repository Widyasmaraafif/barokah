<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSettingsStore } from '@/stores/settings';

const name = usePage().props.name;
const { getSettingValue, loadSettings } = useSettingsStore();
void loadSettings();

function logoUrl(): string {
    return getSettingValue<string>('branding.logo_url', '');
}
</script>

<template>
    <div
        class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md"
    >
        <img v-if="logoUrl()" :src="logoUrl()" :alt="String(name)" class="size-full object-contain bg-white p-1" />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
