import { computed, reactive, watch } from 'vue';

export type CartItem = {
    productId: number;
    slug: string;
    name: string;
    price: number;
    image: string | null;
    stock: number;
    quantity: number;
};

const storageKey = 'barokah.cart';
const state = reactive<{ items: CartItem[] }>({ items: [] });
let hydrated = false;

function hydrate(): void {
    if (hydrated || typeof window === 'undefined') {
        return;
    }

    hydrated = true;
    try {
        const stored = JSON.parse(window.localStorage.getItem(storageKey) ?? '[]');
        if (Array.isArray(stored)) {
            state.items = stored;
        }
    } catch {
        state.items = [];
    }
}

watch(() => state.items, (items) => {
    if (hydrated && typeof window !== 'undefined') {
        window.localStorage.setItem(storageKey, JSON.stringify(items));
    }
}, { deep: true });

export function useCartStore() {
    hydrate();

    function add(item: Omit<CartItem, 'quantity'>, quantity = 1): void {
        const existing = state.items.find((entry) => entry.productId === item.productId);
        if (existing) {
            existing.quantity = Math.min(existing.quantity + quantity, existing.stock);
            return;
        }
        state.items.push({ ...item, quantity: Math.min(quantity, item.stock) });
    }

    function update(productId: number, quantity: number): void {
        const item = state.items.find((entry) => entry.productId === productId);
        if (!item) return;
        item.quantity = Math.max(1, Math.min(quantity, item.stock));
    }

    function remove(productId: number): void {
        state.items = state.items.filter((item) => item.productId !== productId);
    }

    function clear(): void {
        state.items = [];
    }

    const count = computed(() => state.items.reduce((sum, item) => sum + item.quantity, 0));
    const subtotal = computed(() => state.items.reduce((sum, item) => sum + item.price * item.quantity, 0));

    return { state, count, subtotal, add, update, remove };
}
