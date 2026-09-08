<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shells for admin store management (spec §17). Data flows
 * through the admin JSON API; the controller only gates and renders.
 */
class SellerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Sellers/Index');
    }

    public function show(Seller $seller): Response
    {
        return Inertia::render('Admin/Sellers/Show', [
            'seller' => $seller->load(['user', 'products']),
        ]);
    }

    public function edit(Seller $seller): Response
    {
        return Inertia::render('Admin/Sellers/Edit', [
            'seller' => $seller->load(['user', 'products']),
        ]);
    }
}
