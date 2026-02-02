<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report - {{ $generatedAt }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.6;
        }

        .container { padding: 20px; }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #8B5CF6;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            color: #1F2937;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 13px;
            color: #6B7280;
            font-weight: normal;
        }

        .meta-info {
            text-align: center;
            font-size: 10px;
            color: #6B7280;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .summary-row { display: table-row; }

        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 12px;
            text-align: center;
            border: 1px solid #E5E7EB;
            background: #F9FAFB;
        }

        .summary-label {
            font-size: 9px;
            color: #6B7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1F2937;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #E5E7EB;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        thead {
            background: #F3F4F6;
        }

        th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 2px solid #D1D5DB;
        }

        td {
            padding: 7px 6px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 10px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-green { background: #D1FAE5; color: #065F46; }
        .badge-amber { background: #FEF3C7; color: #92400E; }
        .badge-red { background: #FEE2E2; color: #991B1B; }
        .badge-blue { background: #DBEAFE; color: #1E40AF; }
        .badge-purple { background: #E9D5FF; color: #6B21A8; }

        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
            font-size: 9px;
            color: #6B7280;
        }

        .page-break { page-break-after: always; }

        .product-img {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Inventory Report</h1>
            <h2>Stock Levels & Product Performance Analysis</h2>
        </div>

        <div class="meta-info">
            Generated: <strong>{{ $generatedAt }}</strong>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-card">
                    <div class="summary-label">Total Products</div>
                    <div class="summary-value" style="color: #3B82F6;">{{ number_format($totalProducts) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Stock</div>
                    <div class="summary-value" style="color: #10B981;">{{ number_format($totalStock) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Sold</div>
                    <div class="summary-value" style="color: #8B5CF6;">{{ number_format($totalSold) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Stock Value</div>
                    <div class="summary-value" style="color: #F59E0B;">৳{{ number_format($totalValue, 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="section">
            <div class="section-title">Product Inventory Details ({{ count($products) }} Products)</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 28%;">Product Name</th>
                        <th style="width: 10%;">SKU</th>
                        <th style="width: 12%;">Category</th>
                        <th style="width: 8%;">Type</th>
                        <th class="text-right" style="width: 10%;">Stock</th>
                        <th class="text-right" style="width: 10%;">Sold</th>
                        <th class="text-right" style="width: 10%;">Price</th>
                        <th class="text-center" style="width: 8%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $index => $product)
                        @php
                            $stock = $product->computed_stock ?? 0;
                            $sold = $product->computed_sold ?? 0;
                            $reorderLevel = $product->reorder_level ?? 10;

                            if ($stock === 0) {
                                $status = 'Out of Stock';
                                $badgeClass = 'badge-red';
                            } elseif ($stock <= $reorderLevel) {
                                $status = 'Low Stock';
                                $badgeClass = 'badge-amber';
                            } else {
                                $status = 'In Stock';
                                $badgeClass = 'badge-green';
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                {{ $product->name }}
                                @if($product->variation_count > 0)
                                    <span style="font-size: 8px; color: #7C3AED; font-weight: bold;">({{ $product->variation_count }} variation{{ $product->variation_count > 1 ? 's' : '' }})</span>
                                @endif
                            </td>
                            <td>{{ $product->sku ?? 'N/A' }}</td>
                            <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                            <td>
                                <span class="badge {{ $product->type === 'simple' ? 'badge-blue' : 'badge-purple' }}">
                                    {{ $product->type === 'simple' ? 'Simple' : 'Variable' }}
                                </span>
                            </td>
                            <td class="text-right"><strong>{{ number_format($product->computed_stock ?? 0) }}</strong></td>
                            <td class="text-right">{{ number_format($product->computed_sold ?? 0) }}</td>
                            <td class="text-right">৳{{ number_format($product->display_price ?? $product->price ?? 0, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                            </td>
                        </tr>

                        @if(($index + 1) % 25 === 0 && $index + 1 < count($products))
                            </tbody></table>
                            <div class="page-break"></div>
                            <table><thead>
                                <tr>
                                    <th style="width: 4%;">#</th>
                                    <th style="width: 28%;">Product Name</th>
                                    <th style="width: 10%;">SKU</th>
                                    <th style="width: 12%;">Category</th>
                                    <th style="width: 8%;">Type</th>
                                    <th class="text-right" style="width: 10%;">Stock</th>
                                    <th class="text-right" style="width: 10%;">Sold</th>
                                    <th class="text-right" style="width: 10%;">Price</th>
                                    <th class="text-center" style="width: 8%;">Status</th>
                                </tr>
                            </thead><tbody>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Auxtech E-commerce Platform</strong> - Inventory Management Report</p>
            <p>This is a system-generated report. For queries, contact your administrator.</p>
        </div>
    </div>
</body>
</html>
