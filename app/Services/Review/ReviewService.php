<?php

namespace App\Services\Review;

use App\Helpers\ImageHelper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Create a review (for authenticated users and guests)
     */
    public function createReview(array $data): Review
    {
        // Validate if user can review
        $this->validateReviewPermission($data);

        // Handle image uploads
        $images = $this->handleImageUploads($data['images'] ?? []);

        return Review::create([
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'product_id' => $data['product_id'],
            'order_id' => $data['order_id'] ?? null,
            'name' => $data['name'],
            'review_message' => $data['review_message'],
            'images' => $images,
            'rating' => $data['rating'] ?? null,
            'is_approved' => false, // User reviews need approval
        ]);
    }

    /**
     * Create admin review (auto-approved)
     */
    public function createAdminReview(array $data): Review
    {
        // Handle image uploads
        $images = $this->handleImageUploads($data['images'] ?? []);

        return Review::create([
            'user_id' => null, // Admin reviews don't belong to users
            'session_id' => null,
            'product_id' => $data['product_id'],
            'order_id' => null, // No order for admin reviews
            'name' => $data['name'],
            'review_message' => $data['review_message'],
            'images' => $images,
            'rating' => $data['rating'] ?? null,
            'is_approved' => true, // Admin reviews are auto-approved
        ]);
    }

    /**
     * Get paginated reviews for a product
     */
    public function getProductReviews(int $productId, array $params = []): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 3;

        return Review::where('product_id', $productId)
            ->approved()
            ->with(['user:id,name', 'order:id,order_number'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if user/session can review a product
     */
    public function canUserReviewProduct(int|string $userIdOrSessionId, int $productId, bool $isGuest = false): bool
    {
        if ($isGuest) {
            // Check if session has delivered order with this product
            return OrderItem::whereHas('order', function ($query) use ($userIdOrSessionId) {
                $query->where('session_id', $userIdOrSessionId)
                      ->where('status', 'delivered');
            })->where('product_id', $productId)->exists();
        } else {
            // Check if user has delivered order with this product
            return OrderItem::whereHas('order', function ($query) use ($userIdOrSessionId) {
                $query->where('user_id', $userIdOrSessionId)
                      ->where('status', 'delivered');
            })->where('product_id', $productId)->exists();
        }
    }

    /**
     * Approve a review
     */
    public function approveReview(int $reviewId): bool
    {
        $review = Review::findOrFail($reviewId);
        return $review->update(['is_approved' => true]);
    }

    /**
     * Get pending reviews for admin
     */
    public function getPendingReviews(): LengthAwarePaginator
    {
        return Review::pending()
            ->with(['user:id,name', 'product:id,name', 'order:id,order_number'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Validate review permission
     */
    private function validateReviewPermission(array $data): void
    {
        $userId = $data['user_id'] ?? null;
        $sessionId = $data['session_id'] ?? null;
        $productId = $data['product_id'];

        if ($userId) {
            if (!$this->canUserReviewProduct($userId, $productId, false)) {
                throw new \Exception('You must purchase and receive this product before reviewing it.');
            }
        } elseif ($sessionId) {
            if (!$this->canUserReviewProduct($sessionId, $productId, true)) {
                throw new \Exception('You must purchase and receive this product before reviewing it.');
            }
        } else {
            throw new \Exception('User authentication required.');
        }

        // Check for duplicate reviews
        $query = Review::where('product_id', $productId);
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($query->exists()) {
            throw new \Exception('You have already reviewed this product.');
        }
    }

    /**
     * Handle multiple image uploads
     */
    private function handleImageUploads(array $images): array
    {
        $uploadedImages = [];

        if (!empty($images)) {
            foreach ($images as $image) {
                if ($image && is_uploaded_file($image)) {
                    $uploadedImages[] = ImageHelper::uploadImage($image, 'storage/reviews');
                }
            }
        }

        return $uploadedImages;
    }

    /**
     * Get formatted reviews for product details page (for frontend)
     */
    public function getFormattedProductReviews($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $reviews->map(function ($review) {
            $userName = $review->user ? $review->user->name : $review->name;
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->review_message,
                'images' => $review->images ?? [],
                'created_at' => $review->created_at,
                'time_ago' => $review->created_at->diffForHumans(),
                'user' => [
                    'name' => $userName,
                    'initials' => $this->getUserInitials($userName),
                ],
            ];
        })->toArray();
    }

    /**
     * Get review statistics for a product
     */
    public function getReviewStats($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->get();
        
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;
        
        $ratingDistribution = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
        ];
    }

    /**
     * Check if user/guest can review a product (for frontend)
     */
    public function canReview($productId, $userId = null, $sessionId = null)
    {
        // Check for existing review
        $existingReview = Review::where('product_id', $productId);
        
        if ($userId) {
            $existingReview->where('user_id', $userId);
        } elseif ($sessionId) {
            $existingReview->where('session_id', $sessionId);
        } else {
            return [
                'can_review' => false,
                'reason' => 'Please login or complete your order to submit a review'
            ];
        }

        if ($existingReview->exists()) {
            return [
                'can_review' => false,
                'reason' => 'You have already reviewed this product'
            ];
        }

        // Check for delivered order
        $hasDelivered = $this->canUserReviewProduct(
            $userId ?? $sessionId, 
            $productId, 
            !$userId
        );

        if (!$hasDelivered) {
            return [
                'can_review' => false,
                'reason' => 'You must purchase and receive this product before reviewing it'
            ];
        }

        return [
            'can_review' => true,
            'reason' => null
        ];
    }

    /**
     * Get user initials from name
     */
    private function getUserInitials($name)
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}