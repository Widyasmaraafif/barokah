<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, edit } from '@/routes/admin/users';

type AdminUserSeller = {
    id: number;
    store_name: string;
    slug: string;
    status: string;
} | null;

type AdminUserDetail = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    address?: string | null;
    state?: string | null;
    post_code?: string | null;
    email_verified_at?: string | null;
    is_admin: boolean;
    is_active_as_seller: boolean;
    is_seller?: boolean;
    seller?: AdminUserSeller;
    created_at?: string;
    updated_at?: string;
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
            <div class="mb-3 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Badge :variant="user.is_admin ? 'default' : 'secondary'">
                        {{ user.is_admin ? 'Admin' : 'Buyer' }}
                    </Badge>
                    <Badge
                        v-if="user.is_active_as_seller"
                        variant="outline"
                    >
                        Seller active
                    </Badge>
                </div>
                <a
                    :href="edit(user.id).url"
                    target="_blank"
                    rel="noopener"
                    class="text-sm font-medium hover:underline"
                >
                    Edit in new tab
                </a>
            </div>

            <table class="w-full text-left text-sm">
                <tbody>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            ID
                        </th>
                        <td class="px-3 py-2">{{ user.id }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Name
                        </th>
                        <td class="px-3 py-2">{{ user.name }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Email
                        </th>
                        <td class="px-3 py-2">{{ user.email }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Email verified
                        </th>
                        <td class="px-3 py-2">
                            {{ user.email_verified_at ?? 'Unverified' }}
                        </td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Phone
                        </th>
                        <td class="px-3 py-2">{{ user.phone ?? '-' }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Address
                        </th>
                        <td class="px-3 py-2">{{ user.address ?? '-' }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            State
                        </th>
                        <td class="px-3 py-2">{{ user.state ?? '-' }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Post code
                        </th>
                        <td class="px-3 py-2">{{ user.post_code ?? '-' }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Store
                        </th>
                        <td class="px-3 py-2">
                            <span v-if="user.seller">
                                {{ user.seller.store_name }} ·
                                {{ user.seller.slug }} ·
                                {{ user.seller.status }}
                            </span>
                            <span v-else>-</span>
                        </td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Created at
                        </th>
                        <td class="px-3 py-2">{{ user.created_at ?? '-' }}</td>
                    </tr>
                    <tr class="border-b last:border-0">
                        <th
                            class="text-muted-foreground w-40 px-3 py-2 align-top font-medium"
                        >
                            Updated at
                        </th>
                        <td class="px-3 py-2">{{ user.updated_at ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
