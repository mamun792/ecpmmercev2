<?php

namespace App\Http\Controllers\Admin\Pos;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;

class PosController extends Controller
{

    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }


    public function index(Request $request)
    {
        $products = $this->productService->getAllProductsForAdmin($request);

        // $user = auth()->user();

        $users = User::all();

        //return $user;


        return Inertia::render('Admin/Pos/Index', [
            'products' => $products,
            // 'user' => $user,
            'users' => $users
        ]);

    }

}
