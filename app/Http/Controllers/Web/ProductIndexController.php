<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public product listing page (spec §18.4/Phase 5 pattern, catalog data
 * from Task 4). Filtering mirrors the public API; sold/rating/discount
 * render only when the backend provides them (spec §24 items 21-25).
 */
class ProductIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, ['price_asc', 'price_desc', 'latest', 'name'], true) ? $sort : 'latest';

        $products = Product::query()
            ->active()
            ->with(['seller', 'category', 'images'])
            ->when($request->string('category')->toString() !== '', function ($query) use ($request): void {
                $query->whereHas('category', fn ($category) => $category->where('slug', $request->string('category')->toString()));
            })
            ->when($request->string('search')->toString() !== '', function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($nested) => $nested->where('name', 'like', $search)->orWhere('description', 'like', $search));
            })
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->paginate(24)
            ->withQueryString();

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Product/Index', [
            'products' => ProductResource::collection($products),
            'categories' => CategoryResource::collection($categories),
            'filters' => [
                'category' => $request->string('category')->toString(),
                'search' => $request->string('search')->toString(),
                'sort' => $sort,
            ],
        ]);
    }
}
