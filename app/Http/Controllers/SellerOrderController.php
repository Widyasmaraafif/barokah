<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class SellerOrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Seller/Orders/Index');
    }

    public function show(string $orderNumber): Response
    {
        return Inertia::render('Seller/Orders/Show', [
            'orderNumber' => $orderNumber,
        ]);
    }
}
