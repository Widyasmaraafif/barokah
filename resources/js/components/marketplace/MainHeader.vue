<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Search, ShoppingCart } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useSettingsStore } from '@/stores/settings';

const props = withDefaults(
    defineProps<{
        initialSearch?: string;
    }>(),
    { initialSearch: '' },
);

const emit = defineEmits<{
    search: [value: string];
}>();

const { getSettingValue, loadSettings } = useSettingsStore();
const query = ref(props.initialSearch);
const keywords = computed(() => ['Keripik', 'Hijab', 'Kerudung']);

void loadSettings();

function siteName(): string {
    return getSettingValue<string>('branding.site_name', 'Barokah');
}

function submitSearch(): void {
    emit('search', query.value);
    router.get(
        '/products',
        { search: query.value || undefined },
        { preserveState: false, replace: false },
    );
}

function searchKeyword(keyword: string): void {
    query.value = keyword;
    submitSearch();
}
</script>

<template>
    <div
        class="sticky top-0 z-40 text-white shadow"
        style="background-color: var(--brand-primary)"
    >
        <div
            class="mx-auto flex w-full items-center gap-3 px-4 py-3 md:gap-6"
            style="max-width: var(--container-max); min-height: 76px"
        >
            <Link
                href="/"
                class="flex shrink-0 items-center gap-2"
                aria-label="Marketplace home"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-sm bg-white text-lg font-bold"
                    style="color: var(--brand-primary)"
                    aria-hidden="true"
                >
                    {{ siteName().charAt(0) }}
                </span>
                <span class="hidden text-xl font-bold tracking-tight sm:block">
                    {{ siteName() }}
                </span>
            </Link>

            <div class="min-w-0 flex-1">
                <form
                    class="flex items-center rounded-sm bg-white p-[3px]"
                    role="search"
                    @submit.prevent="submitSearch"
                >
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search products, shops and more"
                        aria-label="Search products"
                        class="h-10 min-w-0 flex-1 rounded-sm bg-transparent px-3 text-sm text-[var(--text-primary)] outline-none"
                    />
                    <button
                        type="submit"
                        class="flex h-10 w-[60px] shrink-0 items-center justify-center rounded-sm text-white"
                        style="background-color: var(--brand-primary)"
                        aria-label="Search"
                    >
                        <Search class="h-5 w-5" />
                    </button>
                </form>
                <div
                    class="mt-1 hidden gap-3 overflow-hidden text-[11px] whitespace-nowrap text-white/90 md:flex"
                >
                    <button
                        v-for="keyword in keywords"
                        :key="keyword"
                        type="button"
                        class="cursor-pointer hover:underline"
                        @click="searchKeyword(keyword)"
                    >
                        {{ keyword }}
                    </button>
                </div>
            </div>

            <button
                type="button"
                class="relative flex h-11 w-12 shrink-0 items-center justify-center rounded-sm transition hover:bg-white/10"
                aria-label="Cart (placeholder, no cart backend)"
                title="Cart is a UI placeholder (spec §24 item 24)"
            >
                <ShoppingCart class="h-6 w-6" />
                <span
                    class="absolute top-0.5 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold"
                    style="color: var(--brand-primary)"
                >
                    0
                </span>
            </button>
        </div>
    </div>
</template>
