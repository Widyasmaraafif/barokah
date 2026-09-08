<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/admin/categories';
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
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                variant="small"
                title="Categories"
                description="Full CRUD via the admin API. Hierarchy (parent_id) is TBC."
            />
            <Button as-child>
                <a :href="create().url" target="_blank" rel="noopener">
                    New category
                </a>
            </Button>
        </div>

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

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b font-medium">
                            <th class="px-3 py-2 font-medium">Category</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Products
                            </th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                            class="hover:bg-muted/50 border-b transition-colors last:border-0"
                        >
                            <td class="px-3 py-2">
                                <p class="font-medium">{{ category.name }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ category.slug }}
                                </p>
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ category.products_count ?? '-' }}
                            </td>
                            <td class="px-3 py-2">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <a
                                        :href="edit(category.id).url"
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
