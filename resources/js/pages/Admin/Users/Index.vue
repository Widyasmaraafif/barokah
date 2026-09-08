<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, edit, show } from '@/routes/admin/users';
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

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Customer</th>
                            <th class="px-3 py-2 font-medium">Role</th>
                            <th class="px-3 py-2 font-medium">Seller</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="show(user.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ user.name }}
                                </Link>
                                <p class="text-muted-foreground text-xs">
                                    {{ user.email }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <Badge
                                    :variant="
                                        user.is_admin ? 'default' : 'secondary'
                                    "
                                >
                                    {{ user.is_admin ? 'Admin' : 'Buyer' }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <Badge
                                    v-if="user.is_active_as_seller"
                                    variant="outline"
                                >
                                    Seller
                                </Badge>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="px-3 py-2">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <Link
                                        :href="show(user.id)"
                                        class="text-muted-foreground text-sm hover:underline"
                                    >
                                        Detail
                                    </Link>
                                    <a
                                        :href="edit(user.id).url"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-sm font-medium hover:underline"
                                    >
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
