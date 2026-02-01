<?php

namespace App\Http\Controllers\Admin\Courier;

use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\CourierSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Couriers\Pathao\PathaoService;

class CourierController extends Controller
{
    protected $pathaoService;

    public function __construct(PathaoService $pathaoService)
    {
        $this->pathaoService = $pathaoService;
    }

    public function index()
    {
        $settings = CourierSetting::first();
        return Inertia::render('Admin/Courier/Index', [
            'settings' => $settings ? [
                'id' => $settings->id,
                'client_id' => $settings->client_id,
                'client_secret' => $settings->client_secret,
                'username' => $settings->username,
                'password' => $settings->password,
                'is_enabled' => $settings->is_enabled,
                'StoreId' => $settings->StoreId,
                'access_token' => $settings->access_token,
                'expires_at' => $settings->expires_at ? $settings->expires_at->toDateTimeString() : null,
            ] : null,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'is_enabled' => 'required|in:yes,no',
                'StoreId' => 'required|integer',
            ]);

            $settings = CourierSetting::first();

            if ($settings) {
                $settings->update($validated);
                $message = 'Courier settings updated successfully';
            } else {
                CourierSetting::create($validated);
                $message = 'Courier settings created successfully';
            }

            return redirect()->back()->with([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Courier Settings Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with([
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Get zones for a specific city
     */
    public function getZonesByCity($cityId)
    {
        try {
            $response = $this->pathaoService->getZonesByCity($cityId);
            return response()->json($response['data'] ?? [], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Get areas for a specific zone
     */
    public function getAreasByZone($zoneId)
    {
        try {
            $response = $this->pathaoService->getAreasByZone($zoneId);
            return response()->json($response['data'] ?? [], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    


    /**
     * Get all cities (already available in your PathaoService)
     */
    public function getAllCities()
    {
        try {
            $response = $this->pathaoService->getCities();
            return response()->json($response['data'] ?? [], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Create a new courier shipment with Pathao
     */
    public function createShipment(Request $request)
    {
        try {
            $validated = $request->validate([
                'orderId' => 'required|integer',
                'recipient_city' => 'required|integer',
                'recipient_zone' => 'required|integer',
                'recipient_area' => 'required|integer',
            ]);

            // Call the Pathao service to create the shipment
            $response = $this->pathaoService->createShipment($validated, $request->input('specialInstruction', 'Need to Delivery before 5 PM'));

            // Extract data from the response
            $responseData = $response['data'] ?? $response;

            $order = $this->pathaoService->updateOrderWithPathaoDetails($validated['orderId'], $validated, $responseData);


            return redirect()->back()->with([
                'success' => true,
                'message' => 'Shipment created successfully',
                'data' => $responseData,
            ]);
        } catch (\Exception $e) {
            Log::error('Pathao Shipment Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with([
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a bulk courier shipment with Pathao
     */
    public function createBulkShipment(Request $request)
    {
        try {
            $validated = $request->validate([
                'orders' => 'required|array',
                'orders.*.orderId' => 'required|integer',
            ]);

            $response = $this->pathaoService->createBulkShipment($validated);

            // Check if the response indicates success
            if (isset($response['success']) && $response['success'] === true) {
                // Update the is_courier column for each order in the payload
                foreach ($validated['orders'] as $orderData) {
                    Order::where('id', $orderData['orderId'])->update([
                        'is_courier' => true,
                        'courier_name' => 'pathao'
                    ]);
                }
            }

            return redirect()->back()->with([
                'success' => true,
                'message' => 'Bulk shipment created successfully',
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Pathao Bulk Shipment Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with([
                'error' => $e->getMessage(),
            ]);
        }
    }


}
