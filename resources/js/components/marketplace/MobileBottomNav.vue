<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, House, ShoppingCart, User } from '@lucide/vue';
import { computed } from 'vue';
import { home, login } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { index as productsIndex } from '@/routes/products';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useCartStore } from '@/stores/cart';

const page = usePage();
const authUser = computed(
    () =>
        (page.props as unknown as { auth?: { user?: { id?: number } } })
            .auth?.user,
);
const { isCurrentUrl } = useCurrentUrl();
const { count: cartCount } = useCartStore();

const items = computed(() => [
    { label: 'Home', href: home(), icon: House, placeholder: false },
    {
        label: 'Feed',
        href: productsIndex(),
        icon: Bell,
        placeholder: true,
    },
    {
        label: `Cart (${cartCount.value})`,
        href: '/cart',
        icon: ShoppingCart,
        placeholder: false,
    },
    {
        label: 'Me',
        href: authUser.value ? profileEdit() : login(),
        icon: User,
        placeholder: false,
    },
]);
</script>

<template>
    <nav
        aria-label="Mobile navigation"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-[var(--border-default)] bg-white md:hidden"
        style="min-height: 56px"
    >
        <div class="grid grid-cols-4">
            <Link
                v-for="item in items"
                :key="item.label"
                :href="item.href"
                :title="
                    item.placeholder
                        ? `${item.label} is a UI placeholder (TBC, no backend)`
                        : item.label
                "
                :class="[
                    'flex min-h-[56px] flex-col items-center justify-center gap-0.5 text-[11px]',
                    isCurrentUrl(item.href)
                        ? 'font-semibold text-[var(--brand-primary)]'
                        : 'text-[var(--text-muted)]',
                ]"
            >
                <component :is="item.icon" class="h-5 w-5" />
                {{ item.label }}
            </Link>
        </div>
    </nav>
</template>
