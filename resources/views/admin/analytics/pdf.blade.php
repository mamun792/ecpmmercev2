<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analytics Report</title>
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
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1F2937;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #6B7280;
            font-size: 12px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 15px;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            text-align: center;
        }
        .stat-card h3 {
            color: #6B7280;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .stat-card .value {
            color: #1F2937;
            font-size: 18px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #1F2937;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background: #3B82F6;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 10px;
        }
        table tr:nth-child(even) {
            background: #F9FAFB;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
            color: #6B7280;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Analytics Report</h1>
            <p>Generated on {{ date('F j, Y \a\t g:i A') }}</p>
            <p>Date Range: {{ $dateRange }}</p>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Today Revenue</h3>
                <div class="value">৳{{ number_format($analytics['today']['revenue'], 0) }}</div>
            </div>
            <div class="stat-card">
                <h3>Today Orders</h3>
                <div class="value">{{ $analytics['today']['orders'] }}</div>
            </div>
            <div class="stat-card">
                <h3>Avg Order Value</h3>
                <div class="value">৳{{ number_format($analytics['averageOrderValue'], 0) }}</div>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="value">{{ $analytics['totalProducts'] }}</div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="section">
            <h2>Top 10 Selling Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $product['name'] }}</td>
                        <td>{{ $product['sold_count'] }}</td>
                        <td>৳{{ number_format($product['revenue'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Orders by Status -->
        <div class="section">
            <h2>Orders by Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordersByStatus as $status)
                    <tr>
                        <td>{{ ucfirst($status['status']) }}</td>
                        <td>{{ $status['count'] }}</td>
                        <td>{{ $status['percentage'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Courier Stats -->
        <div class="section">
            <h2>Courier Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Courier</th>
                        <th>Orders</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Steadfast</td>
                        <td>{{ $courierStats['steadfast']['count'] }}</td>
                        <td>{{ $courierStats['steadfast']['percentage'] }}%</td>
                    </tr>
                    <tr>
                        <td>Pathao</td>
                        <td>{{ $courierStats['pathao']['count'] }}</td>
                        <td>{{ $courierStats['pathao']['percentage'] }}%</td>
                    </tr>
                    <tr>
                        <td>None</td>
                        <td>{{ $courierStats['none']['count'] }}</td>
                        <td>{{ $courierStats['none']['percentage'] }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- District Stats -->
        <div class="section">
            <h2>Top Districts by Orders</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>District</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($districtStats as $index => $district)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $district['district'] }}</td>
                        <td>{{ $district['count'] }}</td>
                        <td>৳{{ number_format($district['revenue'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a system-generated report | {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
