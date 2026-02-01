<?php

namespace App\Http\Controllers\Admin\Courier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Couriers\Pathao\PathaoSuccessRateService;
use App\Services\Couriers\Steadfast\SteadfastService;
use Illuminate\Support\Facades\Log;

class FraudcheckController extends Controller
{
    protected $PathaoSuccessRateService;
    protected $steadfastService;

    public function __construct(PathaoSuccessRateService $pathaoSuccessRateService, SteadfastService $steadfastService)
    {
        $this->PathaoSuccessRateService = $pathaoSuccessRateService;
        $this->steadfastService = $steadfastService;
    }


    public function fetchSuccessRate(Request $request)
    {
        $phone = $request->input('phone');

        // Fetch data from both services
        $pathaoResponse = $this->fetchPathaoData($phone);
        $steadfastResponse = $this->fetchSteadfastData($phone);

        // Decode JSON responses to arrays
        $pathaoData = json_decode($pathaoResponse->getContent(), true);
        $steadfastData = json_decode($steadfastResponse->getContent(), true);

        // Calculate aggregated metrics
        $aggregated = $this->calculateAggregatedMetrics($pathaoData, $steadfastData);

        // Prepare response data
        $data = [
            'phone' => $phone,
            'pathao' => $pathaoData,
            'steadfast' => $steadfastData,
            'aggregated' => $aggregated,
        ];

        return response()->json($data);
    }

    private function fetchPathaoData($phone)
    {
        $pathao = $this->PathaoSuccessRateService->successRate($phone);
        Log::info('Pathao success rate response: ' . json_encode($pathao));
        return response()->json([
            'success' => true,
            'message' => 'Success rate fetched successfully',
            'success_rate' => $pathao['data']['success_rate'] ?? null,
            'customer_number' => $pathao['data']['customer']['customer_number'] ?? null,
            'successful_delivery' => $pathao['data']['customer']['successful_delivery'] ?? 0,
            'total_delivery' => $pathao['data']['customer']['total_delivery'] ?? 0,
            'fraud_level' => $pathao['data']['customer']['fraud_level'] ?? 0,
            'fraud_count' => $pathao['data']['customer']['fraud_count'] ?? 0,
        ]);
    }

    private function fetchSteadfastData($phoneNumber)
    {
        $result = $this->steadfastService->checkFraud($phoneNumber);
        return response()->json($result);
    }

    private function calculateAggregatedMetrics(array $pathaoData, array $steadfastData)
    {
        // Extract relevant metrics
        $pathaoSuccess = $pathaoData['successful_delivery'] ?? 0;
        $pathaoTotal = $pathaoData['total_delivery'] ?? 0;
        $pathaoCancel = $pathaoTotal - $pathaoSuccess; // Assuming cancel = total - success

        $steadfastSuccess = $steadfastData['success'] ?? 0;
        $steadfastCancel = $steadfastData['cancel'] ?? 0;
        $steadfastTotal = $steadfastData['total'] ?? 0;

        // Calculate aggregated metrics
        $totalSuccess = $pathaoSuccess + $steadfastSuccess;
        $totalCancel = $pathaoCancel + $steadfastCancel;
        $totalOrders = $pathaoTotal + $steadfastTotal;

        // Calculate success rate
        $successRate = $totalOrders > 0 ? ($totalSuccess / $totalOrders) * 100 : 0;
        $successRate = round($successRate, 2); // Round to 2 decimal places

        return [
            'total_success' => $totalSuccess,
            'total_cancel' => $totalCancel,
            'total_orders' => $totalOrders,
            'success_rate' => $successRate,
        ];
    }
}
