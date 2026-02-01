<?php

namespace App\Http\Controllers\Admin\LandingPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LandingPage;
use App\Models\LandingPageSetting;
use App\Models\LandingPageProduct;
use App\Helpers\ImageHelper;
use App\Helpers\VideoHelper;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class LandingPageController extends Controller
{
    public function index()
    {
        $landingPages = LandingPage::with(['settings', 'products'])->latest()->paginate(10);

        return Inertia::render('Admin/LandingPages/Index', [
            'landingPages' => $landingPages,
            'frontendUrl' => env('FRONTEND_URL', 'http://localhost:3000')
        ]);
    }

    public function create()
    {
        $products = Product::select('id', 'name', 'feature_image')
            ->paginate(12);
        return Inertia::render('Admin/LandingPages/Create', [
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLandingPage($request);

        DB::beginTransaction();
        try {
            // Create or update landing page
            $landingPage = $this->createOrUpdateLandingPage($request, $validated);
            
            // Handle settings
            $this->handleSettings($landingPage, $request);
            
            // Handle products
            $this->handleProducts($landingPage, $request);

            DB::commit();

            return redirect()->route('admin.landing-pages.index')
                ->with('success', 'Landing page created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create landing page: ' . $e->getMessage()]);
        }
    }

    public function edit(LandingPage $landingPage)
    {
        $landingPage->load(['settings', 'products']);
        $products = Product::select('id', 'name', 'feature_image')
            ->paginate(12);

        return Inertia::render('Admin/LandingPages/Edit', [
            'landingPage' => $landingPage,
            'products' => $products
        ]);
    }

public function update(Request $request, LandingPage $landingPage)
{
    // Log raw request for debugging
    // Log::info('Request Method: ' . $request->method());
    // Log::info('Request All: ', $request->all());
    // Log::info('Request Files: ', $request->allFiles());
    // Log::info('Content Type: ' . $request->header('Content-Type'));
    
    // Important: Convert checkbox values
    $request->merge([
        'is_enabled_faq' => $request->input('is_enabled_faq', 0),
        'is_enabled_review' => $request->input('is_enabled_review', 0),
        'is_enabled_cta' => $request->input('is_enabled_cta', 0),
    ]);

    $validated = $this->validateLandingPage($request, $landingPage->id);

    //Log::info('Validated Data: ', $validated);

    DB::beginTransaction();
    try {
        $this->createOrUpdateLandingPage($request, $validated, $landingPage);
        $this->handleSettings($landingPage, $request);
        $this->handleProducts($landingPage, $request);

        DB::commit();
        Log::info('Transaction committed successfully');

        return redirect()->route('admin.landing-pages.index')
            ->with('success', 'Landing page updated successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Update failed: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return back()->withErrors(['error' => 'Failed to update landing page: ' . $e->getMessage()]);
    }
}

    public function destroy(LandingPage $landingPage)
    {
        DB::beginTransaction();
        try {
            // Delete all associated images
            $this->deleteImages($landingPage);
            
            $landingPage->delete();
            
            DB::commit();

            return redirect()->route('admin.landing-pages.index')
                ->with('success', 'Landing page deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete landing page: ' . $e->getMessage()]);
        }
    }

    // ============= MODULAR HELPER FUNCTIONS =============

    /**
     * Validate landing page request
     */
    // private function validateLandingPage(Request $request, $id = null)
    // {
    //     return $request->validate([
    //         'title' => 'required|string|max:255',
    //         'slug' => 'nullable|string|max:255|unique:landing_pages,slug,' . $id,
    //         'button_text' => 'nullable|string|max:255',
    //         'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
    //         'youtube_url' => 'nullable|url',
    //         'upload_video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
    //         'product_features_list' => 'nullable|array',
    //         'feature_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
    //         'feature_button_text' => 'nullable|string|max:255',
    //         'cta_title' => 'nullable|string|max:255',
    //         'cta_short_description' => 'nullable|string|max:500',
    //         'cta_button_text' => 'nullable|string|max:255',
    //         'product_gallery' => 'nullable|array',
    //         'product_gallery.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
    //         'faqs' => 'nullable|array',
    //         'review_images' => 'nullable|array',
    //         'review_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
    //         'products' => 'nullable|array',
    //         'products.*' => 'exists:products,id',
    //     ]);
    // }


        protected function validateLandingPage(Request $request, $id = null)
     {
         //Log::info('Validating with data: ', $request->all());

         return $request->validate([
             'title' => 'required|string|max:255',
             'button_text' => 'nullable|string|max:255',
             'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
             'youtube_url' => 'nullable|url',
             'upload_video' => 'nullable|file|mimes:mp4,avi,mov,webm|max:102400',
             'product_features_list' => 'nullable|array',
             'product_features_list.*' => 'nullable|string',
             'feature_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
             'feature_button_text' => 'nullable|string|max:255',
             'cta_title' => 'nullable|string|max:255',
             'cta_short_description' => 'nullable|string',
             'cta_button_text' => 'nullable|string|max:255',
             'product_gallery' => 'nullable|array',
             'product_gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
             'faqs' => 'nullable|array',
             'faqs.*.question' => 'required_with:faqs|string',
             'faqs.*.answer' => 'required_with:faqs|string',
             'review_images' => 'nullable|array',
             'review_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
             'primary_color' => 'nullable|string|max:7',
             'secondary_color' => 'nullable|string|max:7',
             'is_enabled_faq' => 'nullable|boolean',
             'is_enabled_review' => 'nullable|boolean',
             'is_enabled_cta' => 'nullable|boolean',
             'faq_style' => 'nullable|string|max:255',
             'review_style' => 'nullable|string|max:255',
             'cta_style' => 'nullable|string|max:255',
             'faq_order' => 'nullable|string|max:255',
             'review_order' => 'nullable|string|max:255',
             'cta_order' => 'nullable|string|max:255',
             'youtube_video_order' => 'nullable|string|max:255',
             'upload_video_order' => 'nullable|string|max:255',
             'products' => 'nullable|array',
             'products.*' => 'nullable|exists:products,id',
             'deleted_gallery_images' => 'nullable|array',
             'deleted_review_images' => 'nullable|array',
         ]);
     }



    /**
     * Create or update landing page
     */
    private function createOrUpdateLandingPage(Request $request, array $validated, ?LandingPage $landingPage = null)
    {
        $data = [
            'title' => $request->input('title'),
            'slug' => Str::slug($request->input('title')),
            'button_text' => $request->input('button_text') ?? null,
            'youtube_url' => $request->input('youtube_url') ?? null,
            'feature_button_text' => $request->input('feature_button_text') ?? null,
            'cta_title' => $request->input('cta_title') ?? null,
            'cta_short_description' => $request->input('cta_short_description') ?? null,
            'cta_button_text' => $request->input('cta_button_text') ?? null,
        ];

        Log::info('Data being saved to landing page:', $data);

        // Handle hero image
        $data['hero_image'] = $this->handleHeroImage($request, $landingPage);

        // Handle feature image
        $data['feature_image'] = $this->handleFeatureImage($request, $landingPage);

        // Handle video upload
        $data['upload_video'] = $this->handleVideoUpload($request, $landingPage);

        // Handle product features list
        $data['product_features_list'] = $this->handleProductFeatures($request);

        // Handle product gallery
        $data['product_gallery'] = $this->handleProductGallery($request, $landingPage);

        // Handle FAQs
        $data['faqs'] = $this->handleFaqs($request);

        // Handle review images
        $data['review_images'] = $this->handleReviewImages($request, $landingPage);

        if ($landingPage) {
            $landingPage->update($data);
            return $landingPage;
        }

        return LandingPage::create($data);
    }

    /**
     * Handle hero image upload
     */
    private function handleHeroImage(Request $request, ?LandingPage $landingPage = null)
    {
        if ($request->hasFile('hero_image')) {
            // Delete old image if exists
            if ($landingPage && $landingPage->hero_image) {
                ImageHelper::deleteImage($landingPage->hero_image);
            }
            return ImageHelper::uploadImage($request->file('hero_image'), 'storage/landing-pages/hero');
        }

        return $landingPage->hero_image ?? null;
    }

    /**
     * Handle feature image upload
     */
    private function handleFeatureImage(Request $request, ?LandingPage $landingPage = null)
    {
        if ($request->hasFile('feature_image')) {
            // Delete old image if exists
            if ($landingPage && $landingPage->feature_image) {
                ImageHelper::deleteImage($landingPage->feature_image);
            }
            return ImageHelper::uploadImage($request->file('feature_image'), 'storage/landing-pages/features');
        }

        return $landingPage->feature_image ?? null;
    }

    /**
     * Handle video upload
     */
    private function handleVideoUpload(Request $request, ?LandingPage $landingPage = null)
    {
        if ($request->hasFile('upload_video')) {
            // Delete old video if exists
            if ($landingPage && $landingPage->upload_video) {
                VideoHelper::deleteVideo($landingPage->upload_video);
            }
            return VideoHelper::uploadVideo($request->file('upload_video'), 'storage/landing-pages/videos');
        }

        return $landingPage->upload_video ?? null;
    }

    /**
     * Handle product features list
     */
    private function handleProductFeatures(Request $request)
    {
        if ($request->has('product_features_list') && is_array($request->product_features_list)) {
            $features = array_filter($request->product_features_list, fn($f) => !empty(trim($f)));
            return !empty($features) ? json_encode(array_values($features)) : null;
        }

        return null;
    }

    /**
     * Handle product gallery images
     */
    private function handleProductGallery(Request $request, ?LandingPage $landingPage = null)
    {
        $existingGallery = $landingPage && $landingPage->product_gallery
            ? $landingPage->product_gallery
            : [];

        if ($request->hasFile('product_gallery')) {
            $galleryPaths = [];

            foreach ($request->file('product_gallery') as $image) {
                $galleryPaths[] = ImageHelper::uploadImage($image, 'storage/landing-pages/gallery');
            }

            // Merge with existing gallery
            $existingGallery = array_merge($existingGallery, $galleryPaths);
        }

        // Handle deleted images
        if ($request->has('deleted_gallery_images')) {
            foreach ($request->deleted_gallery_images as $deletedImage) {
                ImageHelper::deleteImage($deletedImage);
                $existingGallery = array_filter($existingGallery, fn($img) => $img !== $deletedImage);
            }
        }

        return !empty($existingGallery) ? json_encode(array_values($existingGallery)) : null;
    }

    /**
     * Handle FAQs
     */
    private function handleFaqs(Request $request)
    {
        if ($request->has('faqs') && is_array($request->faqs)) {
            $faqs = array_filter($request->faqs, fn($faq) => !empty(trim($faq['question'])) || !empty(trim($faq['answer'])));
            return !empty($faqs) ? json_encode(array_values($faqs)) : null;
        }

        return null;
    }

    /**
     * Handle review images
     */
    private function handleReviewImages(Request $request, ?LandingPage $landingPage = null)
    {
        $existingReviews = $landingPage && $landingPage->review_images
            ? $landingPage->review_images
            : [];

        if ($request->hasFile('review_images')) {
            $reviewPaths = [];

            foreach ($request->file('review_images') as $image) {
                $reviewPaths[] = ImageHelper::uploadImage($image, 'storage/landing-pages/reviews');
            }

            $existingReviews = array_merge($existingReviews, $reviewPaths);
        }

        // Handle deleted review images
        if ($request->has('deleted_review_images')) {
            foreach ($request->deleted_review_images as $deletedImage) {
                ImageHelper::deleteImage($deletedImage);
                $existingReviews = array_filter($existingReviews, fn($img) => $img !== $deletedImage);
            }
        }

        return !empty($existingReviews) ? json_encode(array_values($existingReviews)) : null;
    }

    /**
     * Handle landing page settings
     */
    private function handleSettings(LandingPage $landingPage, Request $request)
    {
        $settingsData = [
            'primary_color' => $request->input('primary_color'),
            'secondary_color' => $request->input('secondary_color'),
            'is_enabled_faq' => $request->boolean('is_enabled_faq'),
            'is_enabled_review' => $request->boolean('is_enabled_review'),
            'is_enabled_cta' => $request->boolean('is_enabled_cta'),
            'faq_style' => $request->input('faq_style'),
            'review_style' => $request->input('review_style'),
            'cta_style' => $request->input('cta_style'),
            'faq_order' => $request->input('faq_order'),
            'review_order' => $request->input('review_order'),
            'cta_order' => $request->input('cta_order'),
            'youtube_video_order' => $request->input('youtube_video_order'),
            'upload_video_order' => $request->input('upload_video_order'),
        ];

        $landingPage->settings()->updateOrCreate(
            ['landing_page_id' => $landingPage->id],
            $settingsData
        );
    }

    /**
     * Handle landing page products
     */
    protected function handleProducts(LandingPage $landingPage, Request $request)
    {
        Log::info('handleProducts called');
        Log::info('Products from request: ', ['products' => $request->input('products')]);
        
        if ($request->has('products')) {
            $products = $request->input('products', []);
            
            // Ensure it's an array
            if (!is_array($products)) {
                $products = [];
            }
            
            Log::info('Processing products: ', ['products' => $products]);
            
            // Delete existing relationships
            $landingPage->products()->delete();
            
            // Create new relationships
            foreach ($products as $productId) {
                if ($productId) {
                    $landingPage->products()->create([
                        'product_id' => $productId
                    ]);
                }
            }
            
            Log::info('Products synced successfully');
        } else {
            Log::warning('No products in request');
        }
    }

    /**
     * Delete all associated images
     */
    private function deleteImages(LandingPage $landingPage)
    {
        // Delete hero image
        if ($landingPage->hero_image) {
            ImageHelper::deleteImage($landingPage->hero_image);
        }

        // Delete feature image
        if ($landingPage->feature_image) {
            ImageHelper::deleteImage($landingPage->feature_image);
        }

        // Delete video
        if ($landingPage->upload_video) {
            VideoHelper::deleteVideo($landingPage->upload_video);
        }

        // Delete product gallery
        if ($landingPage->product_gallery) {
            $gallery = json_decode($landingPage->product_gallery, true);
            foreach ($gallery as $image) {
                ImageHelper::deleteImage($image);
            }
        }

        // Delete review images
        if ($landingPage->review_images) {
            $reviews = json_decode($landingPage->review_images, true);
            foreach ($reviews as $image) {
                ImageHelper::deleteImage($image);
            }
        }
    }
}
