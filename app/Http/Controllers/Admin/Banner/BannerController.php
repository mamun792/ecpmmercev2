<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use Inertia\Inertia;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index()
    {
        $banner = Banner::first();
        
        return Inertia::render('Admin/Banners/Index', [
            'banner' => $banner
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'top_banner1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'top_banner1_url' => 'nullable|url',
            'top_banner2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'top_banner2_url' => 'nullable|url',
            'top_banner3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'top_banner3_url' => 'nullable|url',
            'new_arrival_b_banner1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'new_arrival_b_banner1_url' => 'nullable|url',
            'new_arrival_b_banner2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'new_arrival_b_banner2_url' => 'nullable|url',
            'random_banners' => 'nullable|array',
            // allow strings for existing images; uploaded files will be validated below
            'random_banners.*.image' => 'nullable',
            'random_banners.*.url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Additional validation for uploaded random banner files because frontend may send existing image paths as strings
        if ($request->hasFile('random_banners')) {
            $uploadedRandomFiles = $request->file('random_banners');
            $fileErrors = [];

            foreach ($uploadedRandomFiles as $index => $files) {
                if (isset($files['image']) && $files['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $fileValidator = Validator::make(['file' => $files['image']], [
                        'file' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                    ]);

                    if ($fileValidator->fails()) {
                        $fileErrors['random_banners.' . $index . '.image'] = $fileValidator->errors()->first('file');
                    }
                }
            }

            if (!empty($fileErrors)) {
                return back()->withErrors($fileErrors)->withInput();
            }
        }

        $banner = Banner::first() ?? new Banner();
        $data = [];

        // Handle top banners
        $topBanners = ['top_banner1', 'top_banner2', 'top_banner3'];
        foreach ($topBanners as $bannerField) {
            if ($request->hasFile($bannerField)) {
                // Delete old image if exists
                if ($banner->$bannerField) {
                    ImageHelper::deleteImage($banner->$bannerField);
                }
                // Upload new image
                $data[$bannerField] = ImageHelper::uploadImage($request->file($bannerField), 'storage/banners');
            }
            
            // Handle URL
            $urlField = $bannerField . '_url';
            if ($request->has($urlField)) {
                $data[$urlField] = $request->$urlField;
            }
        }

        // Handle new arrival banners
        $newArrivalBanners = ['new_arrival_b_banner1', 'new_arrival_b_banner2'];
        foreach ($newArrivalBanners as $bannerField) {
            if ($request->hasFile($bannerField)) {
                // Delete old image if exists
                if ($banner->$bannerField) {
                    ImageHelper::deleteImage($banner->$bannerField);
                }
                // Upload new image
                $data[$bannerField] = ImageHelper::uploadImage($request->file($bannerField), 'storage/banners');
            }
            
            // Handle URL
            $urlField = $bannerField . '_url';
            if ($request->has($urlField)) {
                $data[$urlField] = $request->$urlField;
            }
        }

        // Handle random banners
        if ($request->has('random_banners')) {
            $randomBanners = [];
            $existingRandomBanners = $banner->random_banners ? json_decode($banner->random_banners, true) : [];
            $uploadedRandomFiles = $request->file('random_banners') ?? [];
            
            // Use input array to iterate (urls and possible image paths)
            $inputRandomBanners = $request->input('random_banners', []);

            foreach ($inputRandomBanners as $index => $randomBannerInput) {
                $bannerData = [];

                // If an uploaded file exist for this index, handle upload and delete old file
                if (isset($uploadedRandomFiles[$index]) && isset($uploadedRandomFiles[$index]['image']) && $uploadedRandomFiles[$index]['image'] instanceof \Illuminate\Http\UploadedFile) {
                    if (isset($existingRandomBanners[$index]['image']) && $existingRandomBanners[$index]['image']) {
                        ImageHelper::deleteImage($existingRandomBanners[$index]['image']);
                    }

                    $bannerData['image'] = ImageHelper::uploadImage($uploadedRandomFiles[$index]['image'], 'storage/banners');
                } elseif (isset($randomBannerInput['image']) && is_string($randomBannerInput['image']) && $randomBannerInput['image']) {
                    // Frontend may send existing image as string path
                    $bannerData['image'] = $randomBannerInput['image'];
                } elseif (isset($existingRandomBanners[$index]['image']) && $existingRandomBanners[$index]['image']) {
                    // Keep previously stored image when no new upload provided
                    $bannerData['image'] = $existingRandomBanners[$index]['image'];
                } else {
                    $bannerData['image'] = null;
                }

                $bannerData['url'] = $randomBannerInput['url'] ?? ($existingRandomBanners[$index]['url'] ?? '');
                $randomBanners[] = $bannerData;
            }

            $data['random_banners'] = json_encode($randomBanners);
        }

        if ($banner->exists) {
            $banner->update($data);
        } else {
            Banner::create($data);
        }

        return redirect()->route('admin.banners.index')->with('success', 'Banners updated successfully!');
    }

    public function update(Request $request, Banner $banner)
    {
        return $this->store($request);
    }

    public function deleteRandomBanner(Request $request)
    {
        $banner = Banner::first();
        
        if (!$banner || !$banner->random_banners) {
            return response()->json(['error' => 'No random banners found'], 404);
        }

        $randomBanners = json_decode($banner->random_banners, true);
        $index = $request->index;

        if (isset($randomBanners[$index])) {
            // Delete image file
            if (isset($randomBanners[$index]['image'])) {
                ImageHelper::deleteImage($randomBanners[$index]['image']);
            }
            
            // Remove from array
            unset($randomBanners[$index]);
            $randomBanners = array_values($randomBanners); // Reindex array
            
            $banner->update(['random_banners' => json_encode($randomBanners)]);
            
            return response()->json([
                'success' => 'Random banner deleted successfully',
                'random_banners' => $randomBanners
            ]);
        }

        return response()->json(['error' => 'Random banner not found'], 404);
    }

    public function deleteRandomBannerImage(Request $request)
    {
        $banner = Banner::first();
        
        if (!$banner || !$banner->random_banners) {
            return response()->json(['error' => 'No random banners found'], 404);
        }

        $randomBanners = json_decode($banner->random_banners, true);
        $index = $request->index;

        if (isset($randomBanners[$index])) {
            // Delete image file
            if (isset($randomBanners[$index]['image'])) {
                ImageHelper::deleteImage($randomBanners[$index]['image']);
                $randomBanners[$index]['image'] = null;
            }
            
            $banner->update(['random_banners' => json_encode($randomBanners)]);
            
            return response()->json([
                'success' => 'Random banner image deleted successfully',
                'random_banners' => $randomBanners
            ]);
        }

        return response()->json(['error' => 'Random banner not found'], 404);
    }

    public function deleteBanner(Request $request)
    {
        $banner = Banner::first();
        
        if (!$banner) {
            return response()->json(['error' => 'Banner not found'], 404);
        }

        $field = $request->field;
        $allowedFields = [
            'top_banner1', 'top_banner2', 'top_banner3', 
            'new_arrival_b_banner1', 'new_arrival_b_banner2'
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        if ($banner->$field) {
            ImageHelper::deleteImage($banner->$field);
            $banner->update([$field => null, $field . '_url' => null]);
            
            return response()->json(['success' => 'Banner deleted successfully']);
        }

        return response()->json(['error' => 'Banner not found'], 404);
    }
}
