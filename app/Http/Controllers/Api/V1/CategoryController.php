<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public category listing (spec §11.2).
 */
class CategoryController extends Controller
{
    /**
     * List active categories with active product counts.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }
}
