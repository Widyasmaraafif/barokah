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
    status: string;
    seller: { store_name: string; slug: string } | null;
    category: { name: string; slug: string } | null;
    images: DetailImage[] | { data: DetailImage[] };
    primary_image: string | null;
};

const props = defineProps<{ product: DetailProduct }>();

const { formatAmount, loadSettings } = useSettingsStore();
const { startBuy } = useCheckoutStore();
const activeImage = ref(props.product.primary_image);

void loadSettings();

function unwrapImages(images: DetailProduct['images']): DetailImage[] {
    return Array.isArray(images) ? images : (images?.data ?? []);
}

const gallery = computed(() => unwrapImages(props.product.images));

const isOutOfStock = computed(() => props.product.stock <= 0);

function selectImage(url: string): void {
    activeImage.value = url;
}
</script>

<template>
    <Head :title="product.name" />

    <MarketplaceLayout>
        <div class="mx-auto w-full max-w-[1200px] px-4 py-6">
        <nav class="mb-4 text-xs text-muted-foreground">
            <Link href="/products" class="hover:underline">Products</Link>
            <span v-if="product.category"> / {{ product.category.name }}</span>
            <span> / {{ product.name }}</span>
        </nav>

        <div class="grid gap-6 rounded border bg-white p-4 md:grid-cols-2">
            <div>
                <div class="aspect-square overflow-hidden rounded border">
                    <img
                        v-if="activeImage"
                        :src="activeImage"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center text-sm text-muted-foreground"
                    >
                        No image available
                    </div>
                </div>
                <div v-if="gallery.length > 1" class="mt-2 flex gap-2">
                    <button
                        v-for="image in gallery"
                        :key="image.id"
                        type="button"
                        :class="[
                            'h-16 w-16 overflow-hidden rounded border',
                            activeImage === image.url
                                ? 'border-[var(--brand-primary)]'
                                : 'border-transparent',
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

            <div>
                <h1 class="text-xl font-medium">{{ product.name }}</h1>
                <p
                    v-if="product.seller"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    Sold by {{ product.seller.store_name }}
                </p>

                <div
                    class="mt-4 rounded bg-[var(--brand-primary-soft)] p-4"
                >
                    <p
                        class="text-3xl font-semibold text-[var(--brand-primary)]"
                    >
                        {{ formatAmount(Number(product.price)) }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            isOutOfStock
                                ? 'Out of stock'
                                : `${product.stock} in stock`
                        }}
                    </p>
                </div>

                <p
                    v-if="product.description"
                    class="mt-4 text-sm leading-relaxed whitespace-pre-line"
                >
                    {{ product.description }}
                </p>

                <div class="mt-6 flex gap-2">
                    <Link
                        :href="`/checkout/${product.slug}`"
                        :class="[
                            'flex h-12 flex-1 items-center justify-center rounded bg-[var(--brand-primary)] px-6 font-semibold text-white',
                            isOutOfStock ? 'pointer-events-none opacity-50' : '',
                        ]"
                        @click="startBuy(product.id, product.slug)"
                    >
                        Buy Now
                    </Link>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    Direct checkout only. Cart is a UI placeholder (spec
                    §24 item 24).
                </p>
            </div>
        </div>
        </div>
        </MarketplaceLayout>
</template>
