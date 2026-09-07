<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
