<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shells for admin payment management (spec §17). Refunds
 * are TBC (spec §17), so the UI is list + detail only.
 */
class PaymentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Payments/Index');
    }

    public function show(Payment $payment): Response
    {
        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment->load('order'),
        ]);
    }
}
