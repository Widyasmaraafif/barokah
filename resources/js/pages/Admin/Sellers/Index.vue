<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index, edit, show } from '@/routes/admin/sellers';
import { fetchAdminList } from '../useAdminList';

type AdminSeller = {
    id: number;
    store_name: string;
    slug: string;
    status: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Stores',
                href: index(),
            },
        ],
    },
});

const sellers = ref<AdminSeller[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
            return 'default';
        case 'suspended':
            return 'destructive';
        default:
            return 'secondary';
    }
}

onMounted(async () => {
    try {
        sellers.value = await fetchAdminList<AdminSeller>('/api/v1/admin/sellers');
    } catch {
        error.value = 'Stores are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Stores" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Stores"
            description="Seller stores. Approval workflow is TBC — status updates persist without notifications."
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

            <p v-else-if="sellers.length === 0" class="text-muted-foreground text-sm">
                No stores yet.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Store</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="seller in sellers"
                            :key="seller.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Link
                                    :href="show(seller.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ seller.store_name }}
                                </Link>
                                <p class="text-muted-foreground text-xs">
                                    {{ seller.slug }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <Badge :variant="statusVariant(seller.status)">
                                    {{ seller.status }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <Link
                                        :href="show(seller.id)"
                                        class="text-muted-foreground text-sm hover:underline"
                                    >
                                        Detail
                                    </Link>
                                    <a
                                        :href="edit(seller.id).url"
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
