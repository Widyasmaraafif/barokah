<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SellerProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Seller/Products/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Seller/Products/Create');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('Seller/Products/Edit', [
            'product' => $product->load(['category', 'images']),
        ]);
    }
}
