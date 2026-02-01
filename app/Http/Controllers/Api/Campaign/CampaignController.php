<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Get active campaigns with products
     */
    public function getActiveCampaigns()
    {
        $campaigns = Campaign::active()
            ->with(['products', 'products.category.parentRecursive', 'products.variations', 'products.variations.attributes.value.attribute', 'products.campaigns'])
            ->get();

        // Calculate time remaining for each campaign
        $campaigns->transform(function ($campaign) {
            $endDate = \Carbon\Carbon::parse($campaign->end_date);
            $now = now();
            $timeRemaining = $endDate->diffInSeconds($now, false); // false to get negative if past

            $campaign->time_remaining = max(0, $timeRemaining); // Ensure non-negative
            return $campaign;
        });

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }
}