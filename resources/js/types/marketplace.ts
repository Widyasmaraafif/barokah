/**
 * Marketplace frontend contracts (spec §18.4).
 *
 * Price is always in MYR and rendered via `formatAmount()` — never `Rp`
 * or manual `RM ` concatenation. `soldCount`/`rating`/`discount` render
 * only when the backend provides them; UI placeholders omit them otherwise
 * (spec §24 items 21–25 — no promo/ranking/live/cart backends).
 */
export type ProductCardData = {
    id: string;
    slug: string;
    name: string;
    image: string | null;
    price: number;
    originalPrice?: number;
    discountPercent?: number;
    rating?: number;
    soldCount?: number;
    location?: string;
    badges?: string[];
    freeShipping?: boolean;
    stockLabel?: string;
};

export type MarketplaceCategory = {
    id: string;
    name: string;
    slug: string;
    image: string | null;
    href: string;
    productsCount?: number;
};

export type HomeProductItem = {
    id: number;
    name: string;
    slug: string;
    price: string | number;
    stock: number;
    status: string;
    category: { id: number; name: string; slug: string } | null;
    images: { id: number; url: string; sort_order: number; is_primary: boolean }[];
    primary_image: string | null;
};

export type HomeSellerItem = {
    id: number;
    store_name: string;
    slug: string;
    description: string | null;
    profile_photo_url: string | null;
    city: string | null;
    state: string | null;
    store_location?: string | null;
    phone?: string | null;
    whatsapp?: string | null;
    status: string;
};

export type HomeCategoryItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    products_count?: number;
};

/**
 * Map a Task 4 product payload onto the card contract. Only fields the
 * backend actually returns are mapped — no invented sold/rating/discount.
 */
export function toProductCardData(product: HomeProductItem): ProductCardData {
    return {
        id: String(product.id),
        slug: product.slug,
        name: product.name,
        image: product.primary_image,
        price: Number(product.price),
        stockLabel: product.stock <= 0 ? 'Out of stock' : `${product.stock} in stock`,
    };
}

export function toMarketplaceCategory(category: HomeCategoryItem): MarketplaceCategory {
    return {
        id: String(category.id),
        name: category.name,
        slug: category.slug,
        image: null,
        href: `/products?category=${category.slug}`,
        productsCount: category.products_count,
    };
}
