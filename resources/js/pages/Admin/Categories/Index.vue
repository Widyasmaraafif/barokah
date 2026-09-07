<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/categories';
import { fetchAdminList } from '../useAdminList';

type AdminCategory = {
    id: number;
    name: string;
    slug: string;
    products_count?: number;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
        ],
    },
});

const categories = ref<AdminCategory[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        categories.value = await fetchAdminList<AdminCategory>('/api/v1/admin/categories');
    } catch {
        error.value = 'Categories are temporarily unavailable.';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Categories" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            variant="small"
            title="Categories"
            description="Full CRUD via the admin API. Hierarchy (parent_id) is TBC."
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

            <p
                v-else-if="categories.length === 0"
                class="text-muted-foreground text-sm"
            >
                No categories yet.
            </p>

            <ul v-else class="divide-y">
                <li
                    v-for="category in categories"
                    :key="category.id"
                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                >
                    <div>
                        <p class="font-medium">{{ category.name }}</p>
                        <p class="text-muted-foreground text-sm">{{ category.slug }}</p>
                    </div>
                    <p
                        v-if="category.products_count !== undefined"
                        class="text-muted-foreground text-sm"
                    >
                        {{ category.products_count }} products
                    </p>
                </li>
            </ul>
        </div>
    </div>
</template>
