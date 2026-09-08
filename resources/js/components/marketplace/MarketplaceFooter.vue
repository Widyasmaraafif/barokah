<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as productsIndex } from '@/routes/products';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { useSettingsStore } from '@/stores/settings';

const { getSettingValue, loadSettings } = useSettingsStore();

void loadSettings();

const contactEmail = computed(() =>
    getSettingValue<string>('contact.email', ''),
);
const siteName = computed(() =>
    getSettingValue<string>('branding.site_name', 'Barokah'),
);
</script>

<template>
    <footer class="mt-5 border-t border-[var(--border-default)] bg-white">
        <div
            class="mx-auto grid w-full gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4"
            style="max-width: var(--container-max)"
        >
            <div>
                <p class="text-base font-bold">{{ siteName }}</p>
                <p class="mt-2 text-xs leading-relaxed text-[var(--text-muted)]">
                    Multi-seller marketplace for physical goods in Malaysia.
                    Branding and contact details come from Settings.
                </p>
            </div>
            <nav aria-label="Shop">
                <p class="text-sm font-semibold">Shop</p>
                <ul class="mt-2 space-y-1.5 text-xs text-[var(--text-secondary)]">
                    <li><Link :href="productsIndex()" class="hover:underline">All products</Link></li>
                    <li><Link :href="productsIndex({ query: { category: 'keripik' } })" class="hover:underline">Keripik</Link></li>
                    <li><Link :href="productsIndex({ query: { category: 'hijab' } })" class="hover:underline">Hijab</Link></li>
                    <li><Link :href="productsIndex({ query: { category: 'kerudung' } })" class="hover:underline">Kerudung</Link></li>
                </ul>
            </nav>
            <nav aria-label="Account">
                <p class="text-sm font-semibold">Account</p>
                <ul class="mt-2 space-y-1.5 text-xs text-[var(--text-secondary)]">
                    <li><Link :href="productsIndex()" class="hover:underline" title="Track order is a UI placeholder (TBC, no backend)">Track order</Link></li>
                    <li><Link :href="sellerDashboard()" class="hover:underline">Seller center</Link></li>
                    <li><Link :href="productsIndex()" class="hover:underline" title="Help is a UI placeholder (TBC, no backend)">Help</Link></li>
                </ul>
            </nav>
            <div>
                <p class="text-sm font-semibold">Contact</p>
                <p
                    v-if="contactEmail"
                    class="mt-2 text-xs text-[var(--text-secondary)]"
                >
                    {{ contactEmail }}
                </p>
                <p v-else class="mt-2 text-xs text-[var(--text-muted)]">
                    Contact details managed in Settings.
                </p>
            </div>
        </div>
        <div class="border-t border-[var(--border-soft)]">
            <p
                class="mx-auto w-full px-4 py-4 text-center text-[11px] text-[var(--text-muted)]"
                style="max-width: var(--container-max)"
            >
                © {{ new Date().getFullYear() }} {{ siteName }}. All rights reserved.
            </p>
        </div>
    </footer>
</template>
