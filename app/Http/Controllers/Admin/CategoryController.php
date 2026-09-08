<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shell for admin category management (spec §13/§17).
 * Data flows through the admin JSON API.
 */
class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
        ]);
    }
}
