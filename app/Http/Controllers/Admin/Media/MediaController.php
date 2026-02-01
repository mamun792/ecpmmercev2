<?php

namespace App\Http\Controllers\Admin\Media;

use Inertia\Inertia;
use App\Models\Media;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::first();
        return Inertia::render('Admin/Media/Index', [
            'media' => $media
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'footer_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'loder_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'footer_payment_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);
    
        $media = Media::find(1);
        $data = [];
    
        foreach (['logo', 'footer_logo', 'favicon', 'loder_logo', 'footer_payment_logo'] as $field) {
            if ($request->hasFile($field)) {
                // Delete old image if exists
                if ($media && $media->$field) {
                    ImageHelper::deleteImage($media->$field);
                }
    
                // Upload and save new image
                $data[$field] = ImageHelper::uploadImage($request->file($field), 'storage/media');
            }
        }
    
        Media::updateOrCreate(['id' => 1], $data);

        Cache::forget('site_info');
    
        return Redirect::route('admin.media.index')->with('success', 'Media updated successfully.');
    }

}
