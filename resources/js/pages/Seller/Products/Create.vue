<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/seller/products';
import RichTextEditor from '@/components/RichTextEditor.vue';

type Category = { id: number; name: string };
const form = reactive({ name: '', description: '', price: '', stock: '0', weight_grams: '0', status: 'draft', category_id: '' });
const categories = ref<Category[]>([]);
const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const images = ref<File[]>([]);

onMounted(async () => {
    const response = await fetch('/api/v1/categories', { headers: { Accept: 'application/json' } });
    if (response.ok) categories.value = ((await response.json()) as { data: Category[] }).data;
});

function csrfToken(): string { return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''; }
function onFiles(event: Event): void { images.value = Array.from((event.target as HTMLInputElement).files ?? []); }
async function save(): Promise<void> {
    isSaving.value = true; errors.value = {};
    const data = new FormData();
    for (const [key, value] of Object.entries(form)) data.append(key, value);
    for (const image of images.value) data.append('images[]', image);
    const response = await fetch('/api/v1/seller/products', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() }, body: data });
    if (response.ok) { router.visit(index()); return; }
    const body = (await response.json()) as { errors?: Record<string, string[]> };
    for (const [field, messages] of Object.entries(body.errors ?? {})) errors.value[field] = messages[0] ?? 'Invalid value.';
    isSaving.value = false;
}
</script>

<template>
    <Head title="Add product" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link :href="index()" class="text-muted-foreground w-fit text-sm hover:underline">← Back to products</Link>
        <Heading variant="small" title="Add product" description="Add product to your store." />
        <form class="max-w-2xl space-y-5 rounded-xl border p-4" @submit.prevent="save">
            <div class="grid gap-2"><Label for="name">Name</Label><Input id="name" v-model="form.name" required /><InputError :message="errors.name" /></div>
            <div class="grid gap-2"><Label for="description">Description</Label><RichTextEditor v-model="form.description"/><InputError :message="errors.description" /></div>
            <div class="grid grid-cols-2 gap-4"><div class="grid gap-2"><Label for="price">Price</Label><Input id="price" v-model="form.price" type="number" min="0" step="0.01" required /><InputError :message="errors.price" /></div><div class="grid gap-2"><Label for="stock">Stock</Label><Input id="stock" v-model="form.stock" type="number" min="0" required /><InputError :message="errors.stock" /></div></div>
            <div class="grid grid-cols-2 gap-4"><div class="grid gap-2"><Label for="category_id">Category</Label><select id="category_id" v-model="form.category_id" required class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"><option value="">Select category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><InputError :message="errors.category_id" /></div><div class="grid gap-2"><Label for="status">Status</Label><select id="status" v-model="form.status" class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"><option value="draft">Draft</option><option value="active">Active</option><option value="inactive">Inactive</option></select><InputError :message="errors.status" /></div></div>
            <div class="grid gap-2"><Label for="weight_grams">Weight (grams)</Label><Input id="weight_grams" v-model="form.weight_grams" type="number" min="0" required /><InputError :message="errors.weight_grams" /></div>
            <div class="grid gap-2"><Label for="images">Images</Label><Input id="images" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="onFiles" /><InputError :message="errors.images" /></div>
            <Button type="submit" :disabled="isSaving">{{ isSaving ? 'Saving…' : 'Save product' }}</Button>
        </form>
    </div>
</template>
