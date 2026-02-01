<?php

namespace App\Http\Controllers\Admin\GeneralSetting;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GeneralSetting\GeneralSettingService;
use App\Http\Requests\GeneralSetting\GeneralSettingRequest;
use Illuminate\Support\Facades\Cache;

class GeneralSettingController extends Controller
{
    protected $generalSettingService;

    public function __construct(GeneralSettingService $generalSettingService)
    {

        $this->generalSettingService = $generalSettingService;
        
    }


    public function socialLinks()
    {
        $settings = $this->generalSettingService->getSettings();
        
        return Inertia::render('Admin/GeneralSettings/SocialLinks', [
            'settings' => $settings
        ]);
    }

    public function basicInformation(){
        $settings = $this->generalSettingService->getSettings();
        
        return Inertia::render('Admin/GeneralSettings/BasicInformation', [
            'settings' => $settings
        ]);
    }


    public function store(GeneralSettingRequest $request)
    {
        // $request->validated() will automatically validate and return validated data
        $validatedData = $request->validated();

        $settings = $this->generalSettingService->updateOrCreateSettings($validatedData);

         Cache::forget('site_info');

        return redirect()->back()
            ->with('success', 'Settings updated successfully');
    }


}
