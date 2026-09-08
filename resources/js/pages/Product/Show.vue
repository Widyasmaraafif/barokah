<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue';
import { useCheckoutStore } from '@/stores/checkout';
import { useSettingsStore } from '@/stores/settings';

type DetailImage = {
    id: number;
    url: string;
    sort_order: number;
    is_primary: boolean;
};

type DetailProduct = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string | number;
    stock: number;
    weight_grams?: number;
    status: string;
    seller: { store_name: string; slug: string } | null;
    category: { name: string; slug: string } | null;
    images: DetailImage[] | { data: DetailImage[] };
    primary_image: string | null;
};

const props = defineProps<{
    product: DetailProduct | { data: DetailProduct };
}>();

const { formatAmount, loadSettings } = useSettingsStore();
const { startBuy } = useCheckoutStore();

/**
 * A single JsonResource serializes through Inertia as `{ data: {...} }`;
 * unwrap it so the template reads the product directly (same convention as
 * the collection pages).
 */
const product = computed<DetailProduct>(() => {
    const raw = props.product as DetailProduct | { data: DetailProduct };
    if (raw && 'data' in raw && raw.data && typeof raw.data === 'object') {
        return raw.data;
    }
    return raw as DetailProduct;
});

const activeImage = ref(product.value.primary_image);
const quantity = ref(1);

void loadSettings();

function unwrapImages(images: DetailProduct['images']): DetailImage[] {
    return Array.isArray(images) ? images : (images?.data ?? []);
}

const gallery = computed(() => unwrapImages(product.value.images));

const isOutOfStock = computed(() => product.value.stock <= 0);

const weightLabel = computed(() =>
    product.value.weight_grams != null
        ? `${product.value.weight_grams} g`
        : null,
);

function selectImage(url: string): void {
    activeImage.value = url;
}

function increment(): void {
    if (quantity.value < product.value.stock) {
        quantity.value += 1;
    }
}

function decrement(): void {
    if (quantity.value > 1) {
        quantity.value -= 1;
    }
}
</script>

<template>
    <Head :title="product.name" />

    <MarketplaceLayout>
        <div class="mx-auto w-full max-w-[1200px] px-4 py-6 pb-24 md:pb-6">
            <!-- Breadcrumb -->
            <nav class="mb-4 text-xs text-[var(--text-muted)]">
                <Link href="/products" class="hover:underline">Products</Link>
                <span v-if="product.category">
                    <span class="mx-1">/</span>
                    <Link
                        :href="`/products?category=${product.category.slug}`"
                        class="hover:underline"
                    >
                        {{ product.category.name }}
                    </Link>
                </span>
                <span class="mx-1">/</span>
                <span class="text-[var(--text-primary)]">
                    {{ product.name }}
                </span>
            </nav>

            <div class="grid gap-6 rounded-sm border border-[var(--border-default)] bg-white p-4 md:grid-cols-2 md:gap-10 md:p-6">
                <!-- Gallery -->
                <div class="md:sticky md:top-24 md:self-start">
                    <div class="aspect-square overflow-hidden rounded-sm border border-[var(--border-default)] bg-[var(--bg-muted)]">
                        <img
                            v-if="activeImage"
                            :src="activeImage"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-sm text-[var(--text-muted)]"
                        >
                            No image available
                        </div>
                    </div>
                    <div
                        v-if="gallery.length > 1"
                        class="mt-3 flex gap-2 overflow-x-auto pb-1"
                    >
                        <button
                            v-for="image in gallery"
                            :key="image.id"
                            type="button"
                            :aria-label="`View image ${image.sort_order + 1}`"
                            :class="[
                                'h-16 w-16 shrink-0 overflow-hidden rounded-sm border-2',
                                activeImage === image.url
                                    ? 'border-[var(--brand-primary)]'
                                    : 'border-transparent hover:border-[var(--border-default)]',
                            ]"
                            @click="selectImage(image.url)"
                        >
                            <img
                                :src="image.url"
                                :alt="product.name"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>
                </div>

                <!-- Info -->
                <div class="flex flex-col">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-semibold text-[var(--text-primary)] md:text-2xl">
                                {{ product.name }}
                            </h1>
                            <p
                                v-if="product.seller"
                                class="mt-1.5 text-sm text-[var(--text-secondary)]"
                            >
                                Sold by
                                <span class="font-medium text-[var(--text-primary)]">
                                    {{ product.seller.store_name }}
                                </span>
                            </p>
                        </div>
                        <span
                            :class="[
                                'shrink-0 rounded-sm px-2 py-1 text-[11px] font-semibold',
                                isOutOfStock
                                    ? 'bg-[var(--accent-red)] text-white'
                                    : 'bg-[var(--accent-cyan)]/10 text-[var(--accent-cyan)]',
                            ]"
                        >
                            {{ isOutOfStock ? 'Out of stock' : 'In stock' }}
                        </span>
                    </div>

                    <!-- Price panel -->
                    <div class="mt-5 rounded-sm bg-[var(--brand-primary-soft)] p-4">
                        <p class="text-3xl font-semibold tracking-tight text-[var(--brand-primary)]">
                            {{ formatAmount(Number(product.price)) }}
                        </p>
                        <p v-if="!isOutOfStock" class="mt-1 text-sm text-[var(--text-secondary)]">
                            {{ product.stock }} available
                        </p>
                    </div>

                    <!-- Key facts -->
                    <dl class="mt-5 grid grid-cols-2 gap-3 rounded-sm border border-[var(--border-soft)] p-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-[11px] text-[var(--text-muted)]">Category</dt>
                            <dd class="mt-0.5 font-medium text-[var(--text-primary)]">
                                {{ product.category?.name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-[var(--text-muted)]">Weight</dt>
                            <dd class="mt-0.5 font-medium text-[var(--text-primary)]">
                                {{ weightLabel ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-[var(--text-muted)]">Stock</dt>
                            <dd class="mt-0.5 font-medium text-[var(--text-primary)]">
                                {{ isOutOfStock ? 'Sold out' : `${product.stock} pcs` }}
                            </dd>
                        </div>
                    </dl>

                    <!-- Description -->
                    <p
                        v-if="product.description"
                        class="mt-5 text-sm leading-relaxed whitespace-pre-line text-[var(--text-secondary)]"
                    >
                        {{ product.description }}
                    </p>

                    <!-- Quantity -->
                    <div class="mt-6">
                        <p class="mb-2 text-sm font-medium text-[var(--text-primary)]">
                            Quantity
                        </p>
                        <div
                            class="inline-flex h-11 items-center overflow-hidden rounded-sm border border-[var(--border-default)]"
                        >
                            <button
                                type="button"
                                :disabled="quantity <= 1 || isOutOfStock"
                                class="flex h-full w-10 items-center justify-center text-lg text-[var(--text-secondary)] disabled:opacity-40"
                                aria-label="Decrease quantity"
                                @click="decrement"
                            >
                                −
                            </button>
                            <span class="w-12 text-center text-sm font-semibold text-[var(--text-primary)]">
                                {{ quantity }}
                            </span>
                            <button
                                type="button"
                                :disabled="quantity >= product.stock || isOutOfStock"
                                class="flex h-full w-10 items-center justify-center text-lg text-[var(--text-secondary)] disabled:opacity-40"
                                aria-label="Increase quantity"
                                @click="increment"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            :disabled="isOutOfStock"
                            class="flex h-12 flex-1 items-center justify-center gap-2 rounded-sm border-2 border-[var(--brand-primary)] bg-[var(--brand-primary-soft)] px-6 font-semibold text-[var(--brand-primary)] transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-5 w-5"
                                aria-hidden="true"
                            >
                                <circle cx="8" cy="21" r="1" />
                                <circle cx="19" cy="21" r="1" />
                                <path d="M2.05 2.05h2l2.66 12.54a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57L21 7H6" />
                            </svg>
                            Add to Cart
                        </button>
                        <Link
                            :href="`/checkout/${product.slug}`"
                            :class="[
                                'flex h-12 flex-1 items-center justify-center rounded-sm bg-[var(--brand-primary)] px-6 font-semibold text-white transition hover:bg-[var(--brand-primary-hover)]',
                                isOutOfStock ? 'pointer-events-none opacity-50' : '',
                            ]"
                            @click="startBuy(product.id, product.slug, quantity)"
                        >
                            Buy Now
                        </Link>
                    </div>
                    <p class="mt-2 text-xs text-[var(--text-muted)]">
                        Buying now goes straight to checkout. Add to Cart is a
                        UI placeholder (spec §24 item 24) — no cart backend.
                    </p>
                </div>
            </div>
        </div>
        </MarketplaceLayout>
</template>
