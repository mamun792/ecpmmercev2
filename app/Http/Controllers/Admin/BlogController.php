<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of blogs
     */
    public function index(Request $request)
    {
        $query = Blog::with('user');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $blogs = $query->latest('created_at')->paginate(10);

        return Inertia::render('Admin/Blogs/Index', [
            'blogs' => $blogs,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new blog
     */
    public function create()
    {
        return Inertia::render('Admin/Blogs/Create');
    }

    /**
     * Store a newly created blog
     */
    public function store(Request $request)
    {
        $validated = $this->validateBlog($request);

        // Handle featured image upload using ImageHelper
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = ImageHelper::uploadImage(
                $request->file('featured_image'), 
                'uploads/blogs'
            );
        }

        // Generate unique slug
        $validated['slug'] = $this->generateUniqueSlug($validated['title']);

        // Set user_id to current authenticated user
        $validated['user_id'] = auth()->id();

        // Set published_at if status is published
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        Blog::create($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog created successfully!');
    }

    /**
     * Display the specified blog
     */
    public function show(Blog $blog)
    {
        $blog->load('user');
        
        return Inertia::render('Admin/Blogs/Show', [
            'blog' => $blog
        ]);
    }

    /**
     * Show the form for editing the specified blog
     */
    public function edit(Blog $blog)
    {
        return Inertia::render('Admin/Blogs/Edit', [
            'blog' => $blog
        ]);
    }

    /**
     * Update the specified blog
     */
    public function update(Request $request, Blog $blog)
    {
        $validated = $this->validateBlog($request, $blog->id);

        // Handle featured image upload using ImageHelper
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($blog->featured_image) {
                ImageHelper::deleteImage($blog->featured_image);
            }
            
            $validated['featured_image'] = ImageHelper::uploadImage(
                $request->file('featured_image'), 
                'uploads/blogs'
            );
        }

        // Update slug if title changed
        if ($blog->title !== $validated['title']) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $blog->id);
        }

        // Set published_at logic
        if ($validated['status'] === 'published') {
            // If it wasn't published before, or if it was but we want to keep the original date?
            // User said "automaticly ad current time". 
            // Usually we keep the original published date if it was already published.
            // But if it was draft, we set it to now.
            if ($blog->status !== 'published') {
                $validated['published_at'] = now();
            }
        } else {
            // If status is not published (e.g. draft), published_at should be null?
            // Or keep it? Usually draft implies not published.
            $validated['published_at'] = null;
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog updated successfully!');
    }

    /**
     * Generate a unique slug for the blog
     */
    private function generateUniqueSlug($title, $ignoreId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        $query = Blog::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
            
            $query = Blog::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    /**
     * Remove the specified blog
     */
    public function destroy(Blog $blog)
    {
        // Delete featured image using ImageHelper
        if ($blog->featured_image) {
            ImageHelper::deleteImage($blog->featured_image);
        }

        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog deleted successfully!');
    }

    /**
     * Validate blog data for store and update
     */
    protected function validateBlog(Request $request, $blogId = null)
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:blogs,slug,' . $blogId,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'author_name' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);
    }
}
