<?php

namespace App\Http\Controllers\Admin\SitePage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SitePage;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SitePageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = SitePage::paginate(10); // Fetch all site pages
        return Inertia::render("Admin/SitePage/Index", [
            'pages' => $pages
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/SitePage/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required'
        ]);

        $validated['slug'] = Str::slug($request->name);

        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (SitePage::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        SitePage::create($validated);

        return redirect()->route('admin.site-pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = SitePage::findOrFail($id);
        return Inertia::render('Admin/SitePage/Edit', [
            'page' => $page
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $page = SitePage::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string'
        ]);

        $validated['slug'] = Str::slug($request->name);

        // Ensure slug is unique, excluding current page
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (SitePage::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        $page->update($validated);

        return redirect()->route('admin.site-pages.index')
            ->with('success', 'Page updated successfully.');
    }


    public function updateStatus(Request $request, string $id)
    {
        $page = SitePage::findOrFail($id);
        $page->status = $request->input('status', 0);
        $page->save();

        return redirect()->route('admin.site-pages.index')
            ->with('success', 'Page status updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = SitePage::findOrFail($id);
        $page->delete();

        return redirect()->route('admin.site-pages.index')
            ->with('success', 'Page deleted successfully.');
    }
}
