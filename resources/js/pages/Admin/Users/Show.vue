<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/users';

type AdminUserDetail = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    is_active_as_seller: boolean;
};

defineProps<{
    user: AdminUserDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Customers',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Customer detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Customers
        </Link>
        <Heading
            variant="small"
            :title="user.name"
            :description="user.email"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <p class="text-sm">
                Role: {{ user.is_admin ? 'Admin' : 'Buyer' }}
            </p>
            <p class="text-muted-foreground mt-1 text-sm">
                Seller activation:
                {{ user.is_active_as_seller ? 'active' : 'inactive' }}
            </p>
        </div>
    </div>
</template>
