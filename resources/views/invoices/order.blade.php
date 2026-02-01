<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .invoice-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .invoice-content {
            padding: 30px;
        }

        .company-info {
            display: flex;
            justify-content: end;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .company-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .company-name {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .invoice-meta {
            color: #111111;
            font-size: 14px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .invoice-to, .pay-to {
            flex: 1;
        }

        .invoice-to {
            margin-right: 40px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #050505;
            margin-bottom: 12px;
        }

        .customer-info, .payment-info {
            color: #141414;
            font-size: 14px;
            line-height: 1.5;
        }

        .customer-info div, .payment-info div {
            margin-bottom: 4px;
        }

        .pay-to {
            text-align: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #f9fafb;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table thead {
            background: #f3f4f6;
        }

        .items-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #292929;
            font-size: 14px;
            border-bottom: 1px solid #a3a3a3;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 15px 12px;
            color: #000000;
            font-size: 14px;
            border-bottom: 1px solid #a3a3a3;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody tr:hover {
            background: #f9fafb;
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .totals-table {
            width: 300px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .totals-row.subtotal,
        .totals-row.shipping,
        .totals-row.discount {
            color: #181818;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-row.grand-total {
            font-weight: 700;
            font-size: 16px;
            color: #1f2937;
            border-top: 2px solid #1f2937;
            padding-top: 12px;
            margin-top: 8px;
        }

        .payment-info-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .payment-info-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .payment-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
            color: #141414;
        }

        .payment-detail {
            display: flex;
            gap: 8px;
        }

        .payment-label {
            font-weight: 600;
            min-width: 120px;
        }

        .footer-message {
            text-align: center;
            color: #161616;
            font-size: 14px;
            font-style: italic;
            padding: 20px 0;
            border-top: 1px solid #e5e7eb;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- <div class="invoice-header">
            <div></div>
            <div class="action-buttons">
                <a href="#" class="btn btn-back">← Back</a>
                <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
            </div>
        </div> --}}

        <div class="invoice-content">
            <div class="company-info">
                {{-- <div class="company-logo">
                    <div class="logo-icon">A</div>
                    <span class="company-name">Auxtech</span>
                </div> --}}
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <div class="invoice-meta">
                        <div>Invoice No: #{{ $order['order_number'] }}</div>
                        <div>Date: {{ date('d.m.Y', strtotime($order['created_at'])) }}</div>
                    </div>
                </div>
            </div>

            <div class="invoice-details">
                <div class="invoice-to">
                    <div class="section-title">Invoice To:</div>
                    <div class="customer-info">
                        <div>{{ $order['customer_name'] }}</div>
                        <div>{{ $order['shipping_address'] }}</div>
                        <div>{{ $order['customer_email'] }}</div>
                        <div>{{ $order['customer_phone'] }}</div>
                    </div>
                </div>
                <div class="pay-to">
                    <div class="section-title">Pay To:</div>
                    <div class="payment-info">
                        <div>Ecom</div>
                        <div>{{ $order['shipping_address'] }}</div>
                        <div>{{ $order['customer_email'] }}</div>
                        <div>{{ $order['customer_phone'] }}</div>
                    </div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Attributes</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>
                        @if($item->productVariation && $item->productVariation->attributes && count($item->productVariation->attributes) > 0)
                            @foreach($item->productVariation->attributes as $index => $attr)
                                {{ $attr->value->attribute->name }}: {{ $attr->value->value }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
                </tbody>


            </table>

            <div class="totals-section">
                <div class="totals-table">
                    <div class="totals-row subtotal">
                        <span>Subtotal:</span>
                        <span>৳{{ number_format($order['subtotal'], 2) }}</span>
                    </div>
                    <div class="totals-row shipping">
                        <span>Shipping:</span>
                        <span>৳{{ number_format($order['shipping_cost'], 2) }}</span>
                    </div>
                    <div class="totals-row discount">
                        <span>Discount:</span>
                        <span>-৳{{ number_format($order['discount_total'], 2) }}</span>
                    </div>
                    <div class="totals-row grand-total">
                        <span>Grand Total:</span>
                        <span>৳{{ number_format($order['total'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="payment-info-section">
                <h3>Payment Information:</h3>
                <div class="payment-details">
                    <div class="payment-detail">
                        <span class="payment-label">Payment Method:</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $order['payment_method'])) }}</span>
                    </div>
                    <div class="payment-detail">
                        <span class="payment-label">Payment Status:</span>
                        <span>{{ ucfirst($order['payment_status']) }}</span>
                    </div>
                </div>
            </div>

            <div class="footer-message">
                Thank you for your business!
            </div>
        </div>
    </div>
</body>
</html>