<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/categories';
import { fetchAdminList } from '../useAdminList';

type ParentCategory = {
    id: number;
    name: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
            {
                title: 'New category',
                href: create(),
            },
        ],
    },
});

const form = reactive({
    name: '',
    slug: '',
    parent_id: '' as string,
    description: '',
    is_active: true,
    sort_order: '0',
});

const parents = ref<ParentCategory[]>([]);
const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const notice = ref<string | null>(null);

onMounted(async () => {
    try {
        parents.value = await fetchAdminList<ParentCategory>(
            '/api/v1/admin/categories',
        );
    } catch {
        parents.value = [];
    }
});

function csrfToken(): string {
    return (
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)
            ?.content ?? ''
    );
}

async function save(): Promise<void> {
    isSaving.value = true;
    errors.value = {};
    notice.value = null;

    const payload: Record<string, unknown> = {
        name: form.name.trim(),
        description: form.description.trim() === '' ? null : form.description.trim(),
        is_active: form.is_active,
        sort_order: Number(form.sort_order) || 0,
    };

    if (form.slug.trim() !== '') {
        payload.slug = form.slug.trim();
    }

    if (form.parent_id !== '') {
        payload.parent_id = Number(form.parent_id);
    }

    try {
        const response = await fetch('/api/v1/admin/categories', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

        const data = (await response.json()) as {
            errors?: Record<string, string[]>;
            message?: string;
        };

        if (!response.ok) {
            const first: Record<string, string> = {};
            for (const [field, messages] of Object.entries(data.errors ?? {})) {
                first[field] = messages[0] ?? 'Invalid value.';
            }
            errors.value = first;
            notice.value = data.message ?? 'Category could not be created.';
            return;
        }

        notice.value = 'Category created.';
        form.name = '';
        form.slug = '';
        form.parent_id = '';
        form.description = '';
    } catch {
        notice.value = 'Categories are temporarily unavailable.';
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <Head title="New category" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Categories
        </Link>
        <Heading
            variant="small"
            title="New category"
            description="Opens in its own tab. Slug is optional and auto-generated when left blank."
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl rounded-xl border p-4"
        >
            <p v-if="notice" class="mb-4 text-sm text-amber-600">{{ notice }}</p>

            <form class="space-y-5" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" type="text" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug (optional)</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        placeholder="auto-generated"
                    />
                    <InputError :message="errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="parent_id">Parent (optional)</Label>
                    <select
                        id="parent_id"
                        v-model="form.parent_id"
                        class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="">No parent</option>
                        <option
                            v-for="parent in parents"
                            :key="parent.id"
                            :value="String(parent.id)"
                        >
                            {{ parent.name }}
                        </option>
                    </select>
                    <InputError :message="errors.parent_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="border-input min-h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="sort_order">Sort order</Label>
                        <Input
                            id="sort_order"
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                        />
                        <InputError :message="errors.sort_order" />
                    </div>
                    <div class="flex items-end gap-2 pb-1">
                        <Input
                            id="is_active"
                            type="checkbox"
                            class="h-5 w-5"
                            :checked="form.is_active"
                            @change="
                                form.is_active = (
                                    $event.target as HTMLInputElement
                                ).checked
                            "
                        />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>
                <InputError :message="errors.is_active" />

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">
                        {{ isSaving ? 'Saving…' : 'Create category' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
