<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/sellers';

type AdminSellerDetail = {
    id: number;
    store_name: string;
    slug: string;
    status: string;
    description?: string | null;
};

defineProps<{
    seller: AdminSellerDetail;
}>();

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
</script>

<template>
    <Head title="Store detail" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            :title="seller.store_name"
            :description="`${seller.slug} · ${seller.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        >
            <p class="text-muted-foreground text-sm">
                {{ seller.description ?? 'No description.' }}
            </p>
        </div>
    </div>
</template>
