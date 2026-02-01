<?php

namespace App\Http\Controllers\Frontend\Offer;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;

class OfferController extends Controller
{
    public function __construct(protected ProductService $productService)
    {
    }

    /**
     * Display active campaigns (offers) page.
     */
    public function index(): Response
    {
        $activeCampaigns = $this->productService->getActiveCampaigns();

        return Inertia::render('Frontend/Offers/Index', [
            'activeCampaigns' => $activeCampaigns,
        ]);
    }
}
