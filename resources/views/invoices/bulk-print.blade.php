<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Invoices</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 0;
            size: A4 portrait;
        }

        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f5f5f5;
            padding: 5px;
            margin: 0;
        }

        .invoice_wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            max-width: 148mm;
            margin: 0 auto;
        }

        .invoice-container {
            max-width: 148mm;
            background: white;
            border: 1px solid #e0e0e0;
            width: 100%;
        }

        .invoice_wrapper hr:last-child {
            display: none;

        }

        /* .invoice-container:not(:last-child) {
           padding-bottom: 10mm;
            border-bottom: 1px dotted #000;
        } */

        .invoice-container:nth-child(2n) {
            page-break-after: always;
            margin-bottom: 0;
        }


        .invoice-content {
            padding: 2mm;
        }

        /* Header Section */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8mm;
            padding-bottom: 5mm;
            border-bottom: 2px solid #000;
        }

        .company-logo {
            flex: 0 0 auto;
        }

        .company-logo img {
            max-height: 50px;
            width: auto;
        }

        .company-details {
            flex: 1;
            text-align: center;
            padding: 0 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #000;
            margin-bottom: 3px;
        }

        .company-address {
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .invoice-title-box {
            flex: 0 0 auto;
            text-align: right;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: 700;
            color: #000;
            margin-bottom: 4px;
        }

        .invoice-number {
            font-size: 10px;
            color: #000;
            margin-bottom: 2px;
            text-align: center;
        }

        .barcode {
            margin-top: 4px;
        }

        .barcode svg {
            height: 35px;
            width: auto;
        }

        /* Customer and Order Info */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6mm;
        }

        .customer-info,
        .order-info {
            flex: 1;
        }

        .info-title {
            font-size: 10px;
            font-weight: 600;
            color: #000;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .info-content {
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .info-content div {
            margin-bottom: 2px;
        }

        .order-info {
            text-align: right;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
            font-size: 11px;
        }

        .items-table thead {
            background-color: #f0f0f0;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .items-table th {
            padding: 4px 6px;
            text-align: left;
            font-weight: 600;
            color: #000;
            font-size: 11px;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 4px 6px;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #333;
        }

        .item-image {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border: 1px solid #ddd;
            display: block;
        }

        /* Totals Section */
        .totals-section {
            margin-bottom: 5mm;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 11px;
            color: #333;
            min-width: 200px;
        }

        .totals-row .label {
            text-align: left;
            padding-right: 20px;
        }

        .totals-row .value {
            text-align: right;
        }

        .totals-row.total {
            font-weight: 700;
            font-size: 11px;
            color: #000;
            border-top: 1px solid #333;
            padding-top: 4px;
            margin-top: 2px;
        }

        /* Footer Section */
        .footer-info {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #555;
            padding-top: 5mm;
            border-top: 1px solid #ddd;
        }

        .footer-item {
            flex: 1;
        }

        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: center;

        }

        @media print {
            @page {
                margin: 0;
                size: A4 portrait;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 20mm !important;
            }

            .invoice_wrapper {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 auto;
            }

            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* .invoice-container:nth-child(odd) {
                margin-bottom: 10mm !important;
                border-bottom: 1px dotted #000 !important;
            } */

            .invoice-container:nth-child(2n) {
                page-break-after: always !important;
                break-after: page !important;
            }

            /* .invoice-container:nth-child(odd) {
                margin-bottom: 10mm !important;
            } */

            .invoice-content {
                padding: 3mm !important;
            }
        }
    </style>
</head>

<body>
    {{-- <pre>{{ json_encode($orders) }}</pre> --}}
    <div class="invoice_wrapper">
        @foreach ($orders as $order)
            <div class="invoice-container">
                <div class="invoice-content">

                    <!-- Customer and Order Info -->
                    <div class="info-section">
                        <div class="customer-info">
                            <div class="info-title">Customer Info</div>
                            <div class="info-content">
                                <div><strong>Name:</strong> {{ $order['customer_name'] }}</div>
                                <div><strong>Phone:</strong> {{ $order['customer_phone'] }}</div>
                                <div><strong>Address:</strong> {{ $order['shipping_address'] }}</div>
                            </div>
                        </div>


                            @if ($order['consignment_id'] != null)
                                <div>
                                    <span style="font-size: 15px;">Parcel ID:</span>
                                    <span
                                        style="font-size: 15px;"><strong>{{ $order['consignment_id'] }}</strong></span>
                                </div>
                            @endif

                        <div class="order-info">
                            @if ($logo)
                                <div class="company-logo"
                                    style="width: 100px; display: inline-block; vertical-align: top;">
                                    <img src="{{ asset($logo->logo) }}" alt="Logo" style="width: 100%;">
                                </div>
                            @endif
                            <div class="info-content">
                                <div>Invoice No: <strong>{{ $order['order_number'] }}</strong> </div>
                                <div>{{ $settings->address ?? 'N/A' }}</div>
                                <div>{{ $settings->store_email ?? 'N/A' }}</div>
                                <div>{{ $settings->store_phone_number ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Attributes</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        @if (isset($item->product->feature_image))
                                            <img src="{{ asset($item->product->feature_image) }}" alt="Product"
                                                class="item-image">
                                        @else
                                            <div
                                                style="width:30px;height:30px;background:#f0f0f0;border:1px solid #ddd;">
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item->product->name }}</td>
                                    <td>
                                        @if ($item->productVariation && $item->productVariation->attributes && count($item->productVariation->attributes) > 0)
                                            @foreach ($item->productVariation->attributes as $index => $attr)
                                                {{ $attr->value->attribute->name }}: {{ $attr->value->value }}
                                                @if (!$loop->last)
                                                    ,
                                                @endif
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->unit_price, 0) }} Tk</td>
                                    <td>{{ number_format($item->subtotal, 0) }} Tk</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>


                    <div class="bottom-section">
                        <div>
                            @if ($order['customer_notes'])
                                <div class="customer_notes">
                                    <span style="font-size: 10px;"><strong>Customer Notes:</strong></span>
                                    <span style="font-size: 10px;">{{ $order['customer_notes'] }}</span>
                                </div>
                            @endif
                        </div>
                        <!-- Totals -->
                        <div class="totals-section">

                            <div class="totals-row">
                                <div class="label">Total Qty:</div>
                                <div class="value">{{ $order['items']->sum('quantity') }}</div>
                            </div>
                            <div class="totals-row">
                                <div class="label">Courier Charge:</div>
                                <div class="value">{{ number_format($order['shipping_cost'], 0) }} Tk</div>
                            </div>
                            <div class="totals-row">
                                <div class="label">Total:</div>
                                <div class="value">
                                    {{ number_format($order['subtotal'] + $order['shipping_cost'], 0) }} Tk</div>
                            </div>
                            <div class="totals-row">
                                <div class="label">Discount:</div>
                                <div class="value">{{ number_format($order['discount_total'], 0) }} Tk</div>
                            </div>
                            <div class="totals-row">
                                <div class="label">Paid:</div>
                                <div class="value">
                                    {{ $order['payment_status'] == 'paid' ? number_format($order['total'], 0) : '0' }}
                                    Tk</div>
                            </div>
                            <div class="totals-row total">
                                <div class="label">Cash on Delivery:</div>
                                <div class="value">
                                    {{ $order['payment_status'] == 'paid' ? '0' : number_format($order['total'], 0) }}
                                    Tk</div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer-info">
                        <div class="footer-item">
                            <strong>Order Date:</strong> {{ date('Y-m-d', strtotime($order['created_at'])) }},
                            <strong>Payment Method:</strong>
                            {{ ucfirst(str_replace('_', ' ', $order['payment_method'])) }}
                        </div>
                        <div class="footer-item" style="text-align: right;">
                            <strong>Source:</strong> {{ $order['source'] ?? 'Website' }},
                            <strong>Order Received By:</strong> {{ $order['received_by'] ?? 'Admin' }}
                        </div>
                    </div>
                </div>
            </div>
            <hr style=" width: 100%; border-top: 1px dotted #000; margin: 10mm 0;">
        @endforeach
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>
