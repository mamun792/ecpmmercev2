<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Media;
use App\Models\Order;
use App\Traits\OrderEagerLoading;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    use OrderEagerLoading;

    /**
     * Download single order invoice as PDF
     */
    public function download(Order $order)
    {
        // Use trait-based eager loading for invoices
        $order->load($this->getInvoiceEagerLoads());

        $pdf = Pdf::loadView('invoices.order', ['order' => $order]);
        return $pdf->download('invoice-' . $order->id . '.pdf');
    }

    /**
     * Download multiple order invoices as single PDF
     */
    public function bulkDownload(Request $request)
    {
        $orderIds = $request->input('order_ids');

        // Use trait-based eager loading
        $orders = Order::with($this->getInvoiceEagerLoads())
            ->whereIn('id', $orderIds)
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('invoices.bulk', ['orders' => $orders]);
        return $pdf->download('bulk-invoices.pdf');
    }

    /**
     * Print multiple order invoices in browser
     */
    public function bulkPrint(Request $request)
    {
        $orderIds = explode(',', $request->query('order_ids'));

        // Use trait-based eager loading
        $orders = Order::with($this->getInvoiceEagerLoads())
            ->whereIn('id', $orderIds)
            ->orderBy('id')
            ->get();

        $settings = GeneralSetting::select('app_name', 'address', 'store_email', 'store_phone_number')->first();
        $logo = Media::select('logo')->first();

        return view('invoices.bulk-print', [
            'orders' => $orders,
            'settings' => $settings,
            'logo' => $logo
        ]);
    }
}
