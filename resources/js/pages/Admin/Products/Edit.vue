<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/admin/products';
import { fetchAdminList } from '../useAdminList';
import RichTextEditor from '@/components/RichTextEditor.vue';

type ProductImage = {
    id: number;
    url: string;
    sort_order: number;
    is_primary: boolean;
};

type AdminProductDetail = {
    id: number;
    name: string;
    slug: string;
    status: string;
    price: string | number;
    stock: number;
    weight_grams: number;
    description?: string | null;
    category?: { id: number; name: string; slug: string } | null;
    images?: ProductImage[];
};

type AdminCategoryOption = {
    id: number;
    name: string;
};

const props = defineProps<{
    product: AdminProductDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Products',
                href: index(),
            },
        ],
    },
});

const statuses = ['draft', 'active', 'inactive', 'archived'];

const form = reactive({
    name: props.product.name ?? '',
    description: props.product.description ?? '',
    price: String(props.product.price ?? ''),
    stock: String(props.product.stock ?? ''),
    weight_grams: String(props.product.weight_grams ?? '0'),
    status: props.product.status ?? 'draft',
    category_id:
        props.product.category?.id != null
            ? String(props.product.category.id)
            : '',
});

const categories = ref<AdminCategoryOption[]>([]);
const errors = ref<Record<string, string>>({});
const isSaving = ref(false);
const newImages = ref<File[]>([]);
const removeImageIds = ref<number[]>([]);

onMounted(async () => {
    try {
        categories.value = await fetchAdminList<AdminCategoryOption>(
            '/api/v1/admin/categories',
        );
    } catch {
        categories.value = [];
    }
});

function csrfToken(): string {
    return (
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)
            ?.content ?? ''
    );
}

const newImagePreviews = computed(() =>
    newImages.value.map((file) => URL.createObjectURL(file)),
);

function revokeNewImagePreviews(): void {
    for (const url of newImagePreviews.value) {
        URL.revokeObjectURL(url);
    }
}

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        revokeNewImagePreviews();
        newImages.value = [...newImages.value, ...Array.from(input.files)];
    }
    input.value = '';
}

function removeNewImage(index: number): void {
    newImages.value.splice(index, 1);
}

function markRemoveExistingImage(imageId: number): void {
    if (!removeImageIds.value.includes(imageId)) {
        removeImageIds.value.push(imageId);
    }
}

function undoRemoveImage(imageId: number): void {
    removeImageIds.value = removeImageIds.value.filter((id) => id !== imageId);
}

async function save(): Promise<void> {
    isSaving.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', form.name.trim());
    formData.append('description', form.description.trim());
    formData.append('price', form.price);
    formData.append('stock', form.stock);
    formData.append('weight_grams', form.weight_grams);
    formData.append('status', form.status);

    if (form.category_id !== '') {
        formData.append('category_id', form.category_id);
    }

    for (const file of newImages.value) {
        formData.append('images[]', file);
    }

    for (const id of removeImageIds.value) {
        formData.append('remove_image_ids[]', String(id));
    }

    try {
        const response = await fetch(
            `/api/v1/admin/products/${props.product.id}`,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: formData,
            },
        );

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
            toast.error(data.message ?? 'Product could not be saved.');
            return;
        }

        toast.success('Product saved.');
        newImages.value = [];
        removeImageIds.value = [];

        router.reload({ only: ['product'] });
    } catch {
        toast.error('Products are temporarily unavailable.');
    } finally {
        isSaving.value = false;
    }
}

async function removeProduct(): Promise<void> {
    if (!confirm(`Delete product "${props.product.name}"?`)) {
        return;
    }

    isSaving.value = true;

    try {
        const response = await fetch(
            `/api/v1/admin/products/${props.product.id}`,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            },
        );

        if (!response.ok) {
            toast.error('Product could not be deleted.');
            return;
        }

        toast.success('Product deleted.');
        window.location.href = index().url;
    } catch {
        toast.error('Products are temporarily unavailable.');
    } finally {
        isSaving.value = false;
    }
}

const existingImages = computed(() =>
    (props.product.images ?? []).filter(
        (img) => !removeImageIds.value.includes(img.id),
    ),
);
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Link
            :href="index()"
            class="text-muted-foreground w-fit text-sm hover:underline"
        >
            ← Back to Products
        </Link>
        <Heading
            variant="small"
            :title="`Edit ${product.name}`"
            :description="`${product.slug} · ${product.status}`"
        />

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border max-w-2xl rounded-xl border p-4"
        >
            <form class="space-y-5" @submit.prevent="save">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <RichTextEditor
                        v-model="form.description"
                    />
                    <!-- <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="border-input min-h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    /> -->
                    <InputError :message="errors.description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="price">Price</Label>
                        <Input
                            id="price"
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                        />
                        <InputError :message="errors.price" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="stock">Stock</Label>
                        <Input
                            id="stock"
                            v-model="form.stock"
                            type="number"
                            min="0"
                            required
                        />
                        <InputError :message="errors.stock" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="weight_grams">Weight (grams)</Label>
                        <Input
                            id="weight_grams"
                            v-model="form.weight_grams"
                            type="number"
                            min="0"
                            required
                        />
                        <InputError :message="errors.weight_grams" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="category_id">Category</Label>
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option value="">Keep current</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError :message="errors.category_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>
                </div>

                <!-- Existing images -->
                <div
                    v-if="product.images && product.images.length > 0"
                    class="grid gap-2"
                >
                    <Label>Current images</Label>
                    <div class="flex flex-wrap gap-3">
                        <div
                            v-for="image in existingImages"
                            :key="image.id"
                            class="relative"
                        >
                            <img
                                :src="image.url"
                                :alt="`Image ${image.id}`"
                                :class="[
                                    'h-20 w-20 rounded border object-cover',
                                    removeImageIds.includes(image.id)
                                        ? 'opacity-30'
                                        : '',
                                ]"
                            />
                            <button
                                v-if="!removeImageIds.includes(image.id)"
                                type="button"
                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
                                @click="markRemoveExistingImage(image.id)"
                            >
                                x
                            </button>
                            <button
                                v-else
                                type="button"
                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-gray-500 text-xs text-white"
                                @click="undoRemoveImage(image.id)"
                            >
                                u
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="removeImageIds.length > 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{ removeImageIds.length }} image(s) will be removed on
                        save.
                    </p>
                </div>

                <!-- New images upload -->
                <div class="grid gap-2">
                    <Label for="images">Add images</Label>
                    <Input
                        id="images"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        @change="onFileChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        JPG, PNG, or WebP. Max 2MB each, up to 5 total.
                    </p>
                    <InputError :message="errors['images.0'] ?? errors.images" />
                </div>

                <!-- Preview new images -->
                <div v-if="newImages.length > 0" class="flex flex-wrap gap-3">
                    <div
                        v-for="(file, idx) in newImages"
                        :key="idx"
                        class="relative"
                    >
                        <img
                            :src="newImagePreviews[idx]"
                            :alt="file.name"
                            class="h-20 w-20 rounded border object-cover"
                        />
                        <button
                            type="button"
                            class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
                            @click="removeNewImage(idx)"
                        >
                            x
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="isSaving" type="submit">
                        {{ isSaving ? 'Saving…' : 'Save product' }}
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="isSaving"
                        @click="removeProduct"
                    >
                        Delete
                    </Button>
                    <a
                        :href="show(product.id).url"
                        rel="noopener"
                        class="text-muted-foreground text-sm hover:underline"
                    >
                        View detail
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>
