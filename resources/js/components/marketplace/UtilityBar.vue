<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
</script>

<template>
    <div
        class="hidden text-[12px] text-white md:block"
        style="background-color: var(--brand-primary)"
    >
        <div
            class="mx-auto flex h-[32px] w-full items-center justify-between gap-4"
            style="max-width: var(--container-max)"
        >
            <div class="flex items-center gap-4 px-4">
                <Link href="/products" class="hover:underline">
                    Seller Center
                </Link>
                <span aria-hidden="true" class="opacity-50">|</span>
                <Link href="/products" class="hover:underline"> Help </Link>
            </div>
            <div class="flex items-center gap-4 px-4">
                <span>English / Malay</span>
                <span aria-hidden="true" class="opacity-50">|</span>
                <template v-if="$page.props.auth.user">
                    <Link :href="register()" class="hover:underline">
                        {{ $page.props.auth.user.name }}
                    </Link>
                </template>
                <template v-else>
                    <Link :href="register()" class="hover:underline">
                        Register
                    </Link>
                    <span aria-hidden="true" class="opacity-50">|</span>
                    <Link :href="login()" class="hover:underline">
                        Login
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>
