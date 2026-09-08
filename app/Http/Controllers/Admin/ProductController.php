<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shells for admin product moderation (spec §17). Data
 * flows through the admin JSON API; the controller only gates/renders.
 */
class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Products/Index');
    }

    public function show(Product $product): Response
    {
        return Inertia::render('Admin/Products/Show', [
            'product' => $product->load(['seller', 'category', 'images']),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => $product->load(['seller', 'category', 'images']),
        ]);
    }
}
