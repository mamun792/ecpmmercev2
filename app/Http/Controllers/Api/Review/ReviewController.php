<?php

namespace App\Http\Controllers\Api\Review;

use App\Http\Controllers\Controller;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Store a new review (authenticated users and guests)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'order_id' => 'nullable|integer|exists:orders,id',
                'name' => 'required|string|max:255',
                'review_message' => 'required|string',
                'rating' => 'nullable|integer|min:1|max:5',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'session_id' => 'nullable|string', // For guest users
            ]);

            $data = $request->all();

            // Add user/session info
            if (Auth::check()) {
                $data['user_id'] = Auth::id();
            } elseif ($request->has('session_id')) {
                $data['session_id'] = $request->session_id;
            } else {
                $data['session_id'] = session()->getId();
            }

            $review = $this->reviewService->createReview($data);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully and pending approval.',
                'review' => $review
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store admin review
     */
    public function storeAdmin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'name' => 'required|string|max:255',
                'review_message' => 'required|string',
                'rating' => 'nullable|integer|min:1|max:5',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $review = $this->reviewService->createAdminReview($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Admin review created successfully.',
                'review' => $review
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get reviews for a product
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $reviews = $this->reviewService->getProductReviews($productId, ['per_page' => $perPage]);

            return response()->json([
                'success' => true,
                'data' => $reviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Approve a review (admin only)
     */
    public function approve(int $reviewId): JsonResponse
    {
        try {
            $this->reviewService->approveReview($reviewId);

            return response()->json([
                'success' => true,
                'message' => 'Review approved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get pending reviews (admin only)
     */
    public function pending(): JsonResponse
    {
        try {
            $reviews = $this->reviewService->getPendingReviews();

            return response()->json([
                'success' => true,
                'data' => $reviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check if user can review a product
     */
    public function canReview(Request $request, int $productId): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'nullable|integer|exists:orders,id'
            ]);

            $canReview = false;

            \Illuminate\Support\Facades\Log::info('canReview check RE-DEBUG', [
                'user_id' => Auth::guard('api')->id(),
                'auth_check' => Auth::guard('api')->check(),
                'session_id' => $request->session_id,
                'product_id' => $productId,
                'request_headers' => $request->headers->all()
            ]);

            if (Auth::guard('api')->check()) {
                $canReview = $this->reviewService->canUserReviewProduct(Auth::guard('api')->id(), $productId, false);
            }
            
            // If not allowed by user ID, check session ID (guest purchase or mixed state)
            if (!$canReview && $request->has('session_id')) {
                $sessionCanReview = $this->reviewService->canUserReviewProduct($request->session_id, $productId, true);
                if ($sessionCanReview) {
                    $canReview = true;
                }
            }
            
            \Illuminate\Support\Facades\Log::info('canReview result', ['result' => $canReview]);

            return response()->json([
                'success' => true,
                'can_review' => $canReview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}