<?php

namespace App\Http\Controllers\Admin\Review;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Display the reviews management page
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);

        // Build query based on tab
        $query = Review::with(['user:id,name', 'product:id,name', 'order:id,order_number']);

        if ($tab === 'pending') {
            $query->where('is_approved', false);
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('review_message', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get stats
        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('is_approved', false)->count(),
            'approved' => Review::where('is_approved', true)->count(),
        ];

        // Get products for create form
        $products = Product::select('id', 'name', 'feature_image')->orderBy('name')->get();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'products' => $products,
            'stats' => $stats,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Store a new admin review
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string|max:255',
            'review_message' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $this->reviewService->createAdminReview($request->all());

            Cache::flush();

            return redirect()->back()->with('success', 'Admin review created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create review: ' . $e->getMessage());
        }
    }

    /**
     * Approve a review
     */
    public function approve($reviewId)
    {
        try {
            $this->reviewService->approveReview($reviewId);

            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Review approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve review: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a review
     */
    public function destroy($reviewId)
    {
        try {
            $review = Review::findOrFail($reviewId);

            // Delete associated images if any
            if ($review->images && is_array($review->images)) {
                foreach ($review->images as $image) {
                    $imagePath = public_path($image);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review: ' . $e->getMessage()
            ], 400);
        }
    }
}