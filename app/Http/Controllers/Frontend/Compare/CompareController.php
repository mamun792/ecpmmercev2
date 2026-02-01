<?php

namespace App\Http\Controllers\Frontend\Compare;

use App\Http\Controllers\Controller;
use App\Services\Compare\CompareService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompareController extends Controller
{
    protected $compareService;

    public function __construct(CompareService $compareService)
    {
        $this->compareService = $compareService;
    }

    /**
     * Display compare page
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');
        
        $compareItems = $this->compareService->getCompareList($userId, $sessionId);

        return Inertia::render('Frontend/Compare/Index', [
            'compareItems' => $compareItems
        ]);
    }

    /**
     * Add product to compare list
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');

        $result = $this->compareService->addToCompare($request->product_id, $userId, $sessionId);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Remove product from compare list
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');

        $result = $this->compareService->removeFromCompare($request->product_id, $userId, $sessionId);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Clear compare list
     */
    public function clear(Request $request)
    {
        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');

        $result = $this->compareService->clearCompareList($userId, $sessionId);

        return back()->with('success', $result['message']);
    }
}
