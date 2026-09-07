<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bell, House, ShoppingCart, User } from '@lucide/vue';

const items = [
    { label: 'Home', href: '/', icon: House, active: true },
    { label: 'Feed', href: '/products', icon: Bell, placeholder: true },
    { label: 'Cart', href: '/products', icon: ShoppingCart, placeholder: true },
    { label: 'Me', href: '/products', icon: User, placeholder: true },
];
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
                    item.active
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
