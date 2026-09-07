<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin category management (spec §13/§17): full CRUD, unique slugs,
 * sort order. The self-referencing hierarchy stays TBC (spec §24
 * item 8); parent_id is accepted as nullable only.
 */
class AdminCategoryController extends Controller
{
    /**
     * List all categories with product counts.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return CategoryResource::collection($categories);
    }

    /**
     * Create a category with a unique slug.
     */
    public function store(Request $request): CategoryResource|JsonResponse
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $category = Category::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['slug'] ?? $validated['name']),
        ]);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Update a category, regenerating the slug on rename.
     */
    public function update(Request $request, Category $category): CategoryResource
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (array_key_exists('slug', $validated)) {
            $validated['slug'] = $validated['slug'] === null || $validated['slug'] === ''
                ? $this->uniqueSlug($validated['name'] ?? $category->name, $category->id)
                : $this->uniqueSlug($validated['slug'], $category->id);
        } elseif (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category->id);
        }

        $category->fill($validated)->save();

        return new CategoryResource($category->refresh());
    }

    /**
     * Remove a category.
     */
    public function destroy(Category $category): Response
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base === '' ? Str::random(8) : $base;
        $candidate = $slug;
        $counter = 2;

        while (Category::query()->where('slug', $candidate)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
