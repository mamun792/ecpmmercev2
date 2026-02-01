<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Get all published blogs with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        
        $blogs = Blog::published()
            ->with('user:id,name')
            ->latest('published_at')
            ->paginate($perPage);

        return response()->json($blogs);
    }

    /**
     * Get a single blog by slug
     */
    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->published()
            ->with('user:id,name')
            ->firstOrFail();

        // Increment view count
        $blog->incrementViews();

        return response()->json($blog);
    }

    /**
     * Get latest blogs (for sidebar/widgets)
     */
    public function latest(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $blogs = Blog::published()
            ->latest('published_at')
            ->select('id', 'title', 'slug', 'featured_image', 'published_at')
            ->limit($limit)
            ->get();

        return response()->json($blogs);
    }

    /**
     * Get popular blogs (most viewed)
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $blogs = Blog::published()
            ->orderBy('views_count', 'desc')
            ->select('id', 'title', 'slug', 'featured_image', 'views_count', 'published_at')
            ->limit($limit)
            ->get();

        return response()->json($blogs);
    }

    /**
     * Search blogs
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $perPage = $request->get('per_page', 12);

        $blogs = Blog::published()
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('excerpt', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->latest('published_at')
            ->paginate($perPage);

        return response()->json($blogs);
    }

    /**
     * Get blogs by tag
     */
    public function byTag(Request $request, $tag)
    {
        $perPage = $request->get('per_page', 12);

        $blogs = Blog::published()
            ->whereJsonContains('tags', $tag)
            ->latest('published_at')
            ->paginate($perPage);

        return response()->json($blogs);
    }
}
