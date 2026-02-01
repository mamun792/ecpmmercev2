<?php

namespace App\Http\Controllers\Admin\PromotionalSlider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PromotionalSlider;

use Inertia\Inertia;

use App\Helpers\ImageHelper;

class PromotionalSliderController extends Controller
{


    public function index()
    {
        $sliders = PromotionalSlider::all();
        return Inertia::render('Admin/PromotionalSliders/Index', [
            'sliders' => $sliders
        ]);
    }


    public function create()
    {
        return Inertia::render('Admin/PromotionalSliders/Create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'link' => 'nullable|url'
        ]);

        $imagePath = $request->hasFile('image')
            ? ImageHelper::uploadImage($request->file('image'), 'storage/sliders')
            : null;

        PromotionalSlider::create([
            'image' => $imagePath,
            'link' => $request->link
        ]);

        return redirect()->route('admin.promotional-sliders.index')
            ->with('success', 'Slider created successfully.');
    }

    public function edit(PromotionalSlider $promotionalSlider)
    {
        return Inertia::render('Admin/PromotionalSliders/Edit', [
            'slider' => $promotionalSlider
        ]);
    }

    public function update(Request $request, PromotionalSlider $promotionalSlider)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'link' => 'nullable|url'
        ]);

        $data = [];
        if ($request->hasFile('image')) {
            // Delete old image
            ImageHelper::deleteImage($promotionalSlider->image);
            $data['image'] = ImageHelper::uploadImage($request->file('image'), 'storage/sliders');
        }
        $data['link'] = $request->link;

        $promotionalSlider->update($data);

        return redirect()->route('admin.promotional-sliders.index')
            ->with('success', 'Slider updated successfully.');
    }

    public function destroy(PromotionalSlider $promotionalSlider)
    {
        ImageHelper::deleteImage($promotionalSlider->image);
        $promotionalSlider->delete();

        return redirect()->route('admin.promotional-sliders.index')
            ->with('success', 'Slider deleted successfully.');
    }
}
