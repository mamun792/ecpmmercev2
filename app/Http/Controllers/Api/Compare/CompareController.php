<?php

namespace App\Http\Controllers\Api\Compare;

use App\Http\Controllers\Controller;
use App\Models\CompareList;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompareController extends Controller
{
    private function getOrCreateCompareList($userId, $sessionId = null)
    {
        $sessionId = $sessionId ?: session()->getId();

        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        if (!$compareList) {
            $compareList = CompareList::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_ids' => [],
            ]);
        }

        return $compareList;
    }

    public function addToCompare(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $compareList = $this->getOrCreateCompareList($userId, $request->session_id);

        if (count($compareList->product_ids ?? []) >= 4) {
            return response()->json(['success' => false, 'message' => 'Compare list can have maximum 4 products'], 400);
        }

        if (in_array($request->product_id, $compareList->product_ids ?? [])) {
            return response()->json(['success' => false, 'message' => 'Product already in compare list'], 400);
        }

        $compareList->addProduct($request->product_id);

        return response()->json(['success' => true, 'message' => 'Product added to compare list']);
    }

    public function removeFromCompare(Request $request, $productId)
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $compareList = $this->getOrCreateCompareList($userId, $request->session_id);

        if (!in_array($productId, $compareList->product_ids ?? [])) {
            return response()->json(['success' => false, 'message' => 'Product not in compare list'], 404);
        }

        $compareList->removeProduct($productId);

        return response()->json(['success' => true, 'message' => 'Product removed from compare list']);
    }

    public function getCompareList(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $compareList = $this->getOrCreateCompareList($userId, $request->session_id);
        $products = $compareList->products();

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function clearCompareList(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $compareList = $this->getOrCreateCompareList($userId, $request->session_id);
        $compareList->product_ids = [];
        $compareList->save();

        return response()->json(['success' => true, 'message' => 'Compare list cleared']);
    }
}