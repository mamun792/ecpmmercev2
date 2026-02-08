<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Alert</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .alert-critical {
            background-color: #fee;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .product-item {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 10px 15px;
            margin: 8px 0;
            border-radius: 3px;
        }
        .product-item.low-stock {
            border-left-color: #ffc107;
        }
        .product-name {
            font-weight: bold;
            color: #333;
        }
        .stock-info {
            color: #666;
            font-size: 14px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .summary-table th,
        .summary-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .summary-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Inventory Alert</h1>
            <p>{{ \Carbon\Carbon::now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="content">
            <!-- Summary Section -->
            <div class="alert-info">
                <h3>📊 Summary</h3>
                <table class="summary-table">
                    <tr>
                        <th>Metric</th>
                        <th>Count</th>
                    </tr>
                    @foreach($summary as $key => $value)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td><strong>{{ $value }}</strong></td>
                    </tr>
                    @endforeach
                </table>
            </div>

            <!-- Critical Stock Products -->
            @if(count($criticalProducts) > 0)
            <div class="alert-critical">
                <h3>🚨 Critical Stock Items ({{ count($criticalProducts) }})</h3>
                <p><strong>These products are out of stock or critically low!</strong></p>

                @foreach($criticalProducts as $product)
                <div class="product-item">
                    <div class="product-name">{{ $product['name'] }}</div>
                    <div class="stock-info">
                        Stock: <strong>{{ $product['current_stock'] }}</strong> |
                        Min Required: <strong>{{ $product['minimum_stock'] ?? 0 }}</strong> |
                        Priority: <strong>{{ $product['priority_score'] ?? 'N/A' }}</strong>
                        @if(isset($product['days_until_stockout']))
                            | Days Until Stockout: <strong>{{ $product['days_until_stockout'] }}</strong>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Low Stock Products -->
            @if(count($lowProducts) > 0)
            <div class="alert-warning">
                <h3>⚠️ Low Stock Items ({{ count($lowProducts) }})</h3>
                <p>These products are approaching minimum stock levels.</p>

                @foreach($lowProducts as $product)
                <div class="product-item low-stock">
                    <div class="product-name">{{ $product['name'] }}</div>
                    <div class="stock-info">
                        Stock: <strong>{{ $product['current_stock'] }}</strong> |
                        Min Required: <strong>{{ $product['minimum_stock'] ?? 0 }}</strong> |
                        Priority: <strong>{{ $product['priority_score'] ?? 'N/A' }}</strong>
                        @if(isset($product['days_until_stockout']))
                            | Days Until Stockout: <strong>{{ $product['days_until_stockout'] }}</strong>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Action Required -->
            <div class="alert-info">
                <h3>✅ Recommended Actions</h3>
                <ul>
                    @if(count($criticalProducts) > 0)
                    <li><strong>Immediate Action Required:</strong> Reorder critical stock items to prevent stockouts</li>
                    @endif
                    @if(count($lowProducts) > 0)
                    <li><strong>Plan Reorders:</strong> Schedule purchases for low stock items</li>
                    @endif
                    <li><strong>Review Suppliers:</strong> Contact suppliers for lead times and availability</li>
                    <li><strong>Check Sales Trends:</strong> Analyze if current stock levels match demand patterns</li>
                </ul>

                <p style="text-align: center; margin-top: 20px;">
                    <a href="{{ config('app.url') }}/admin/dashboard" class="btn">
                        View Dashboard
                    </a>
                </p>
            </div>
        </div>

        <div class="footer">
            <p>This email was automatically generated by your Inventory Management System.</p>
            <p>If you no longer wish to receive these alerts, please contact your system administrator.</p>
        </div>
    </div>
</body>
</html>
