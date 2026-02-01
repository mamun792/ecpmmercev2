<?php

namespace App\Http\Controllers\Api\CommonApi;

use App\Models\Media;
use App\Models\Order;
use App\Models\Slider;
use Illuminate\Http\Request;
use App\Models\MarketingTool;
use App\Models\GeneralSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\Categories\CategoryService;
use App\Services\Settings\SettingsService;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\CorporateClient;
use App\Models\LandingPage;
use App\Models\SitePage;
use App\Models\PromotionalSlider;
use App\Models\TeamMember;
use App\Models\Banner;

class CommonApiDataController extends Controller
{
    protected $categoryService;
    protected $settingsService;

    public function __construct(
        CategoryService $categoryService,
        SettingsService $settingsService
    ) {
        $this->categoryService = $categoryService;
        $this->settingsService = $settingsService;
    }
    public function getAllCategories()
    {
        $categories = $this->categoryService->getAllCategoriesForApi();
        return response()->json($categories);
    }

    public function getAllSliders(){
        $sliders = Cache::remember('sliders.all', 3600, function () {
            return Slider::all();
        });
        return response()->json($sliders);
    }

    public function orderData($order_number)
    {
        $orderData = Order::where('order_number', $order_number)
                          ->with('items', 'items.product', 'items.productVariation', 'items.productVariation.attributes.value.attribute', 'items.productVariation.attributes.value')
                          ->first();

        return response()->json($orderData);
    }


    public function getAllAttributes()
    {
        $attributes = Attribute::with('values.attribute')->get();
        return response()->json($attributes);
    }

    public function siteInfos()
    {
        $siteInfo = $this->settingsService->getSiteInfo();
        return response()->json($siteInfo);
    }

    public function ordersByUser($user_id){
        $orders = Order::where('user_id', $user_id)->with('items', 'items.product', 'items.productVariation', 'items.productVariation.attributes.value.attribute', 'items.productVariation.attributes.value')->paginate(10);
        return response()->json($orders);
    }

        public function getAllPages(){
        $pages = SitePage::select('id', 'name', 'slug')
            ->where('status', 1)
            ->get();
        return response()->json($pages);
    }

    public function getPageBySlug($slug){
        $page = SitePage::where('slug', $slug)->first();
        if ($page) {
            return response()->json($page);
        } else {
            return response()->json(['message' => 'Page not found'], 404);
        }
    }



    public function landingPage($slug)
    {
        $data = LandingPage::where('slug', $slug)
            ->with([
                'settings',
                'linkedProducts' => function ($query) {
                    $query->with([
                        'category.parentRecursive',
                        'variations.attributes.value.attribute'
                ]);
                }
            ])
            ->first();

        return response()->json($data);
    }


    public function getAllBrands(){
        $brands = Brand::all();
        return response()->json($brands);
    }


    public function promotionalSliders(){
        $sliders = PromotionalSlider::all();
        return response()->json($sliders);
    }



    public function getTeamMembers(){
        $teamMembers = TeamMember::all();
        return response()->json($teamMembers);
    }



    public function corparateLogo(){
        $logos = CorporateClient::all();
        return response()->json($logos);
    }



}
