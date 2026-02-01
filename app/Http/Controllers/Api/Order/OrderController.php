<?php

namespace App\Http\Controllers\Api\Order;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Services\Order\OrderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $orderService;
    public function __construct(OrderInterface $orderService)
    {
        $this->orderService = $orderService;
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {

        

        //Log::info('Geo Data', ['data' => $geoData]);


        //   Log::info('Order created', $request->all());
        $order = $this->orderService->createOrder($request->all());
        // Log::info('Order created successfully', [
        //     'order_number' => $order->order_number,
        //     'user_id' => $order->user_id,
        //     'customer_email' => $order->customer_email,
        //     'total' => $order->total
        // ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order_number' => $order->order_number,
            'total' => $order->total,
            'order' => $order
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
