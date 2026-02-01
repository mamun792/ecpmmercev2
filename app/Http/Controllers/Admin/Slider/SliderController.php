<?php

namespace App\Http\Controllers\Admin\Slider;

use Inertia\Inertia;
use App\Models\Slider;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Cache;

class SliderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sliders = Slider::all();
        return Inertia::render('Admin/Sliders/Index', [
            'sliders' => $sliders
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|url',
            'bg_color' => 'nullable|string|max:7' // Assuming hex color like #ffffff
        ]);

        $slider = new Slider();
        
        if ($request->hasFile('image')) {
            $imageUrl = ImageHelper::uploadImage($request->file('image'), 'storage/sliders');
            $slider->image = $imageUrl;
        }

        if ($request->hasFile('mobile_image')) {
            $mobileImageUrl = ImageHelper::uploadImage($request->file('mobile_image'), 'storage/sliders');
            $slider->mobile_image = $mobileImageUrl;
        }

        $slider->Link = $request->link;
        $slider->bg_color = $request->bg_color;
        $slider->save();

        // Clear cache
        Cache::forget('sliders.all');

        return Redirect::route('admin.sliders.index')->with('success', 'Slider created successfully.');
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|url',
            'bg_color' => 'nullable|string|max:7'
        ]);
    
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($slider->image) {
                ImageHelper::deleteImage($slider->image);
            }
    
            // Upload new image
            $slider->image = ImageHelper::uploadImage($request->file('image'), 'storage/sliders');
        }

        if ($request->hasFile('mobile_image')) {
            // Delete old mobile image if it exists
            if ($slider->mobile_image) {
                ImageHelper::deleteImage($slider->mobile_image);
            }
    
            // Upload new mobile image
            $slider->mobile_image = ImageHelper::uploadImage($request->file('mobile_image'), 'storage/sliders');
        }
    
        $slider->Link = $request->link;
        $slider->bg_color = $request->bg_color;
        $slider->save();

        // Clear cache
        Cache::forget('sliders.all');
    
        return Redirect::route('admin.sliders.index')->with('success', 'Slider updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Slider $slider)
    {
        // Delete image file
        if ($slider->image) {
            ImageHelper::deleteImage($slider->image);
        }

        if ($slider->mobile_image) {
            ImageHelper::deleteImage($slider->mobile_image);
        }
    
        // Delete record
        $slider->delete();

        // Clear cache
        Cache::forget('sliders.all');
    
        return Redirect::route('admin.sliders.index')->with('success', 'Slider deleted successfully.');
    }

    /**
     * Get all sliders with caching.
     */
    public function getAllSliders()
    {
        $sliders = Cache::remember('sliders.all', 3600, function () {
            return Slider::all();
        });
        
        return response()->json($sliders);
    }
}
