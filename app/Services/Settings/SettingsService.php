<?php

namespace App\Services\Settings;

use App\Models\Media;
use App\Models\Banner;
use App\Models\MarketingTool;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use App\Models\SitePage;

class SettingsService
{
    /**
     * Get all site settings/info
     */
    public function getSiteInfo()
    {
        return Cache::remember('site_info', 3600, function () {
            $generalSettings = GeneralSetting::first();
            $media = Media::first();
            $marketingTools = MarketingTool::all();
            $banners = Banner::first();

            // Clean HTML tags from text fields
            if ($generalSettings) {
                $fieldsToClean = [
                    'attention_notice',
                    'pre_order_notice',
                    'top_notice',
                    'footer_text',
                    'address'
                ];

                foreach ($fieldsToClean as $field) {
                    if (isset($generalSettings->$field)) {
                        // Remove <p>&nbsp;</p> and similar empty HTML tags
                        $cleaned = strip_tags($generalSettings->$field);
                        $cleaned = str_replace('&nbsp;', '', $cleaned);
                        $cleaned = trim($cleaned);
                        $generalSettings->$field = $cleaned ?: null;
                    }
                }
            }

            return [
                'generalSettings' => $generalSettings,
                'media' => $media,
                'marketingTools' => $marketingTools,
                'banners' => $banners
            ];
        });
    }

    /**
     * Get only general settings
     */
    public function getGeneralSettings()
    {
        return Cache::remember('general_settings', 3600, function () {
            $generalSettings = GeneralSetting::first();

            if ($generalSettings) {
                $fieldsToClean = [
                    'attention_notice',
                    'pre_order_notice',
                    'top_notice',
                    'footer_text',
                    'address'
                ];

                foreach ($fieldsToClean as $field) {
                    if (isset($generalSettings->$field)) {
                        $cleaned = strip_tags($generalSettings->$field);
                        $cleaned = str_replace('&nbsp;', '', $cleaned);
                        $cleaned = trim($cleaned);
                        $generalSettings->$field = $cleaned ?: null;
                    }
                }
            }

            return $generalSettings;
        });
    }


    public function getAllPages(){
        $pages = SitePage::select('id', 'name', 'slug')
            ->where('status', 1)
            ->whereNotIn('slug', ['pre-order-policy'])
            ->get();
        return $pages;
    }

    /**
     * Clear settings cache
     */
    public function clearCache()
    {
        Cache::forget('site_info');
        Cache::forget('general_settings');
    }
}
