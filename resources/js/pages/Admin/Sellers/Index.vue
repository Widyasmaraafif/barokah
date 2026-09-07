<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/sellers';
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

            <ul v-else class="divide-y">
                <li
                    v-for="seller in sellers"
                    :key="seller.id"
                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                >
                    <p class="font-medium">{{ seller.store_name }}</p>
                    <p class="text-muted-foreground text-sm">{{ seller.status }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>
