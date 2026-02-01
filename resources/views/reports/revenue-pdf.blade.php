<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Revenue Report - {{ $startDate }} to {{ $endDate }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .container {
            padding: 20px;
            max-width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            color: #1F2937;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 14px;
            color: #6B7280;
            font-weight: normal;
        }

        .period {
            text-align: center;
            font-size: 11px;
            color: #6B7280;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .summary-row {
            display: table-row;
        }

        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 1px solid #E5E7EB;
            background: #F9FAFB;
        }

        .summary-label {
            font-size: 10px;
            color: #6B7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #1F2937;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #E5E7EB;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #F3F4F6;
        }

        th {
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 2px solid #D1D5DB;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 11px;
        }

        tr:hover {
            background: #F9FAFB;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-green {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-amber {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-red {
            background: #FEE2E2;
            color: #991B1B;
        }

        .profit-breakdown {
            background: #F9FAFB;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .breakdown-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .breakdown-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            color: #374151;
        }

        .breakdown-value {
            display: table-cell;
            width: 70%;
            text-align: right;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
            font-size: 10px;
            color: #6B7280;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Revenue Analytics Report</h1>
            <h2>Business Intelligence & Profit Analysis</h2>
        </div>

        <div class="period">
            Report Period: <strong>{{ $startDate }}</strong> to <strong>{{ $endDate }}</strong> | Generated: {{ now()->format('d M Y, h:i A') }}
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-card">
                    <div class="summary-label">Total Revenue</div>
                    <div class="summary-value" style="color: #3B82F6;">৳{{ number_format($summary['total_revenue'], 0) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Net Profit</div>
                    <div class="summary-value" style="color: #10B981;">৳{{ number_format($summary['net_profit'], 0) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Orders</div>
                    <div class="summary-value" style="color: #8B5CF6;">{{ number_format($summary['total_orders']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Avg Order Value</div>
                    <div class="summary-value" style="color: #F59E0B;">৳{{ number_format($summary['avg_order_value'], 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Profit & Cost Breakdown -->
        <div class="section">
            <div class="section-title">Profit & Cost Analysis</div>
            <div class="profit-breakdown">
                <div class="breakdown-row">
                    <div class="breakdown-label">Total Revenue:</div>
                    <div class="breakdown-value" style="color: #3B82F6;">৳{{ number_format($summary['total_revenue'], 2) }}</div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-label">Product Cost:</div>
                    <div class="breakdown-value" style="color: #EF4444;">-৳{{ number_format($summary['product_cost'], 2) }}</div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-label">Shipping Cost:</div>
                    <div class="breakdown-value" style="color: #F59E0B;">-৳{{ number_format($summary['shipping_cost'], 2) }}</div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-label">Discount Given:</div>
                    <div class="breakdown-value" style="color: #6B7280;">-৳{{ number_format($summary['discount_cost'], 2) }}</div>
                </div>
                <div class="breakdown-row" style="border-top: 2px solid #D1D5DB; padding-top: 10px; margin-top: 10px;">
                    <div class="breakdown-label">Net Profit:</div>
                    <div class="breakdown-value" style="color: #10B981; font-size: 16px;">৳{{ number_format($summary['net_profit'], 2) }}</div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-label">Profit Margin:</div>
                    <div class="breakdown-value" style="color: #10B981;">{{ number_format($summary['profit_margin'], 2) }}%</div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        @if(count($top_products) > 0)
        <div class="section">
            <div class="section-title">Top {{ count($top_products) }} Products by Revenue</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 35%;">Product Name</th>
                        <th class="text-right" style="width: 12%;">Units Sold</th>
                        <th class="text-right" style="width: 15%;">Revenue</th>
                        <th class="text-right" style="width: 15%;">Profit</th>
                        <th class="text-right" style="width: 18%;">Profit Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_products as $product)
                    <tr>
                        <td class="text-center">{{ $product['rank'] }}</td>
                        <td>{{ $product['name'] }}</td>
                        <td class="text-right">{{ number_format($product['units_sold']) }}</td>
                        <td class="text-right">৳{{ number_format($product['revenue'], 2) }}</td>
                        <td class="text-right">৳{{ number_format($product['profit'], 2) }}</td>
                        <td class="text-right">
                            @if($product['margin_percentage'] >= 30)
                                <span class="badge badge-green">{{ number_format($product['margin_percentage'], 1) }}%</span>
                            @elseif($product['margin_percentage'] >= 15)
                                <span class="badge badge-amber">{{ number_format($product['margin_percentage'], 1) }}%</span>
                            @else
                                <span class="badge badge-red">{{ number_format($product['margin_percentage'], 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Page Break for Top Customers -->
        @if(count($top_products) > 15)
        <div class="page-break"></div>
        @endif

        <!-- Top Customers -->
        @if(count($top_customers) > 0)
        <div class="section">
            <div class="section-title">Top {{ count($top_customers) }} Customers by Revenue</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">Customer Name</th>
                        <th style="width: 20%;">Phone</th>
                        <th class="text-right" style="width: 15%;">Orders</th>
                        <th class="text-right" style="width: 15%;">Revenue</th>
                        <th class="text-right" style="width: 15%;">Avg Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_customers as $customer)
                    <tr>
                        <td class="text-center">{{ $customer['rank'] }}</td>
                        <td>{{ $customer['name'] }}</td>
                        <td>{{ $customer['phone'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ $customer['orders'] }}</td>
                        <td class="text-right">৳{{ number_format($customer['revenue'], 2) }}</td>
                        <td class="text-right">৳{{ number_format($customer['avg_order'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Payment Methods -->
        @if(count($payment_breakdown) > 0)
        <div class="section">
            <div class="section-title">Payment Method Breakdown</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Payment Method</th>
                        <th class="text-right" style="width: 30%;">Orders</th>
                        <th class="text-right" style="width: 30%;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment_breakdown as $payment)
                    <tr>
                        <td>{{ strtoupper($payment['method'] ?? 'Unknown') }}</td>
                        <td class="text-right">{{ $payment['orders'] }}</td>
                        <td class="text-right">৳{{ number_format($payment['revenue'], 2) }} ({{ number_format($payment['percentage'], 1) }}%)</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Order Status -->
        @if(count($status_breakdown) > 0)
        <div class="section">
            <div class="section-title">Order Status Breakdown</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Status</th>
                        <th class="text-right" style="width: 30%;">Orders</th>
                        <th class="text-right" style="width: 30%;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($status_breakdown as $status)
                    <tr>
                        <td>{{ ucfirst($status['status']) }}</td>
                        <td class="text-right">{{ $status['orders'] }}</td>
                        <td class="text-right">৳{{ number_format($status['revenue'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>Auxtech E-commerce Platform</strong> - Revenue Analytics Report</p>
            <p>This is a system-generated report. For queries, contact your administrator.</p>
        </div>
    </div>
</body>
</html>
