<?php

namespace App\Http\Controllers\Frontend\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\SitePage;

class PagesController extends Controller
{
    /**
     * Display a site page by slug (route model binding uses slug).
     */
    public function show(SitePage $page): Response
    {
        // Only show published pages
        if (!$page->status) {
            abort(404);
        }

        return Inertia::render('Frontend/Pages/Show', [
            'page' => $page,
        ]);
    }
}
