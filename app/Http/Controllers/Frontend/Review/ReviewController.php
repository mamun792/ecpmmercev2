<?php

namespace App\Http\Controllers\Frontend\Review;

use App\Http\Controllers\Controller;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Store a new review (supports both authenticated users and guests)
     */
    public function store(Request $request)
    {
        // Check if user is authenticated
        $isAuthenticated = Auth::check();
        
        // Validate the request
        $rules = [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_message' => 'required|string|min:10|max:1000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
        
        // Only require name for guest users
        if (!$isAuthenticated) {
            $rules['name'] = 'required|string|max:255';
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = [
                'product_id' => $request->product_id,
                'rating' => $request->rating,
                'review_message' => $request->review_message,
            ];

            // Handle image uploads
            if ($request->hasFile('images')) {
                $data['images'] = $request->file('images');
            }

            // For authenticated users
            if (Auth::check()) {
                $data['user_id'] = Auth::id();
                $data['name'] = Auth::user()->name;
            } else {
                // For guest users - use cart_session_id cookie/session
                $data['session_id'] = $request->cookie('cart_session_id') ?? $request->session()->get('cart_session_id');
                $data['name'] = $request->name;
            }

            $this->reviewService->createReview($data);

            return back()->with('success', 'Thank you! Your review has been submitted and is pending admin approval. It will appear on the product page once approved.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Check if user/guest can review a product (AJAX)
     */
    public function canReview($productId)
    {
        $userId = Auth::id();
        // Use cart_session_id cookie for guests (same as cart and order system)
        $sessionId = Auth::check() ? null : (request()->cookie('cart_session_id') ?? request()->session()->get('cart_session_id'));
        
        $result = $this->reviewService->canReview($productId, $userId, $sessionId);
        
        return response()->json($result);
    }
}
