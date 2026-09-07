<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shells for admin customer management (spec §17). Lists
 * and forms fetch data from the admin JSON API (`/api/v1/admin/*`) so
 * the CRUD logic lives in one place; this controller only gates/renders.
 */
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index');
    }

    public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load('seller'),
        ]);
    }
}
