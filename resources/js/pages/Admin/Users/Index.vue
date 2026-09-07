<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/users';
import { fetchAdminList } from '../useAdminList';

type AdminUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    is_active_as_seller: boolean;
};

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

const users = ref<AdminUser[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        users.value = await fetchAdminList<AdminUser>('/api/v1/admin/users');
    } catch {
        error.value = 'Customers are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Customers" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Customers"
            description="Buyers and store owners. Toggle admin capability or seller activation from the API."
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <div v-if="isLoading" class="animate-pulse space-y-2">
                <div class="bg-muted h-10 rounded" />
                <div class="bg-muted h-10 rounded" />
                <div class="bg-muted h-10 rounded" />
            </div>

            <p v-else-if="error" class="text-sm text-amber-600">{{ error }}</p>

            <p v-else-if="users.length === 0" class="text-muted-foreground text-sm">
                No customers yet.
            </p>

            <ul v-else class="divide-y">
                <li
                    v-for="user in users"
                    :key="user.id"
                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                >
                    <div>
                        <p class="font-medium">{{ user.name }}</p>
                        <p class="text-muted-foreground text-sm">{{ user.email }}</p>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ user.is_admin ? 'Admin' : 'Buyer' }}
                        {{ user.is_active_as_seller ? '· Seller' : '' }}
                    </p>
                </li>
            </ul>
        </div>
    </div>
</template>
