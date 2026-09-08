<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue';
import { useSettingsStore } from '@/stores/settings';

type ProductListImage = {
    id: number;
    url: string;
    sort_order: number;
    is_primary: boolean;
};

type ProductListItem = {
    id: number;
    name: string;
    slug: string;
    price: string | number;
    stock: number;
    status: string;
    category: { id: number; name: string; slug: string } | null;
    images: ProductListImage[];
    primary_image: string | null;
};

type ProductCategory = {
    id: number;
    name: string;
    slug: string;
};

type PaginatedProducts = {
    data: ProductListItem[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { total: number };
};

const props = defineProps<{
    products: PaginatedProducts;
    categories: ProductCategory[];
    filters: { category: string; search: string; sort: string };
}>();

const { formatAmount, loadSettings } = useSettingsStore();
const search = ref(props.filters.search);
const sort = ref(props.filters.sort);
const selectedCategory = ref(props.filters.category);

void loadSettings();

const sortOptions = [
    { value: 'latest', label: 'Latest' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
    { value: 'name', label: 'Name' },
];

const isEmpty = computed(() => props.products.data.length === 0);

function applyFilters(): void {
    router.get(
        '/products',
        {
            category: selectedCategory.value || undefined,
            search: search.value || undefined,
            sort: sort.value,
        },
        { preserveState: true, replace: true },
    );
}

function resetFilters(): void {
    search.value = '';
    sort.value = 'latest';
    selectedCategory.value = '';
    router.get('/products', {}, { replace: true });
}
</script>

<template>
    <Head title="Products" />

    <div class="mx-auto w-full max-w-[1200px] px-4 py-6">
        <div class="flex flex-col gap-6 md:flex-row">
            <aside class="w-full shrink-0 md:w-[200px]">
                <div class="rounded border p-4">
                    <h2 class="mb-3 text-sm font-semibold">Categories</h2>
                    <ul class="space-y-1 text-sm">
                        <li>
                            <button
                                type="button"
                                :class="[
                                    'w-full text-left',
                                    selectedCategory === ''
                                        ? 'font-semibold text-[var(--brand-primary)]'
                                        : 'text-muted-foreground',
                                ]"
                                @click="
                                    selectedCategory = '';
                                    applyFilters();
                                "
                            >
                                All products
                            </button>
                        </li>
                        <li
                            v-for="category in categories"
                            :key="category.id"
                        >
                            <button
                                type="button"
                                :class="[
                                    'w-full text-left',
                                    selectedCategory === category.slug
                                        ? 'font-semibold text-[var(--brand-primary)]'
                                        : 'text-muted-foreground',
                                ]"
                                @click="
                                    selectedCategory = category.slug;
                                    applyFilters();
                                "
                            >
                                {{ category.name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </aside>

            <section class="flex-1">
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center"
                >
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search products"
                        class="h-10 flex-1 rounded border px-3 text-sm"
                        @keyup.enter="applyFilters"
                    />
                    <select
                        v-model="sort"
                        class="h-10 rounded border px-3 text-sm"
                        @change="applyFilters"
                    >
                        <option
                            v-for="option in sortOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <p
                    v-if="isEmpty"
                    class="rounded border p-8 text-center text-sm text-muted-foreground"
                >
                    No products found. Try changing filters.
                    <button
                        type="button"
                        class="ml-2 font-medium text-[var(--brand-primary)]"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </p>

                <div
                    v-else
                    class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                >
                    <Link
                        v-for="product in products.data"
                        :key="product.id"
                        :href="`/products/${product.slug}`"
                        class="group overflow-hidden rounded border bg-white transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="aspect-square bg-white">
                            <img
                                v-if="product.primary_image"
                                :src="product.primary_image"
                                :alt="product.name"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-xs text-muted-foreground"
                            >
                                No image
                            </div>
                        </div>
                        <div class="p-2">
                            <p
                                class="line-clamp-2 min-h-9 text-xs leading-snug"
                            >
                                {{ product.name }}
                            </p>
                            <p
                                class="mt-1 text-base font-semibold text-[var(--brand-primary)]"
                            >
                                {{ formatAmount(Number(product.price)) }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ product.stock }} in stock
                            </p>
                        </div>
                    </Link>
                </div>

                <nav
                    v-if="products.links.length > 3"
                    class="mt-6 flex flex-wrap gap-1"
                >
                    <Link
                        v-for="(link, index) in products.links"
                        :key="index"
                        :href="link.url ?? '#'"
                        :class="[
                            'rounded border px-3 py-1 text-sm',
                            link.active
                                ? 'border-[var(--brand-primary)] font-semibold text-[var(--brand-primary)]'
                                : 'text-muted-foreground',
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </section>
        </div>
    </div>
</template>
