<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AnalyticsExport implements WithMultipleSheets
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct($dateFrom = null, $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function sheets(): array
    {
        return [
            new OrdersSheet($this->dateFrom, $this->dateTo),
            new ProductsSheet($this->dateFrom, $this->dateTo),
            new DistrictsSheet($this->dateFrom, $this->dateTo),
        ];
    }
}

class OrdersSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct($dateFrom = null, $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        $query = Order::select('order_number', 'customer_name', 'total', 'status', 'courier_name', 'created_at');

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return ['Order Number', 'Customer', 'Total', 'Status', 'Courier', 'Date'];
    }

    public function title(): string
    {
        return 'Orders';
    }
}

class ProductsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct($dateFrom = null, $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        $query = OrderItem::select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as sold_count'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', '!=', 'cancelled');

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('orders.created_at', [$this->dateFrom, $this->dateTo]);
        }

        return $query->groupBy('products.name')
            ->orderBy('sold_count', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Product Name', 'Units Sold', 'Revenue'];
    }

    public function title(): string
    {
        return 'Products';
    }
}

class DistrictsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct($dateFrom = null, $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        $query = Order::select(
                'shipping_district as district',
                DB::raw('count(*) as order_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('shipping_district');

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
        }

        return $query->groupBy('shipping_district')
            ->orderBy('order_count', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['District', 'Orders', 'Revenue'];
    }

    public function title(): string
    {
        return 'Districts';
    }
}
