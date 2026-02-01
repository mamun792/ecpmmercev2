<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction History Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }

        .filters p {
            margin: 2px 0;
            font-size: 11px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            font-size: 10px;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-processing {
            background-color: #cce7ff;
            color: #004085;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-cancelled {
            background-color: #e2e3e5;
            color: #383d41;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .amount {
            font-weight: bold;
            color: #28a745;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Transaction History Report</h1>
        <p>Generated on: {{ date('F j, Y \a\t g:i A') }}</p>
        <p>Total Records: {{ count($transactions) }}</p>
    </div>

    @if(isset($filters) && !empty(array_filter($filters)))
    <div class="filters">
        <h3>Applied Filters:</h3>
        @if($filters['status'] ?? null)
            <p><strong>Status:</strong> {{ ucfirst($filters['status']) }}</p>
        @endif
        @if($filters['payment_gateway'] ?? null)
            <p><strong>Payment Gateway:</strong> {{ ucfirst($filters['payment_gateway']) }}</p>
        @endif
        @if(($filters['date_from'] ?? null) && ($filters['date_to'] ?? null))
            <p><strong>Date Range:</strong> {{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p>
        @endif
        @if($filters['amount_min'] ?? null)
            <p><strong>Min Amount:</strong> BDT {{ number_format($filters['amount_min'], 2) }}</p>
        @endif
        @if($filters['amount_max'] ?? null)
            <p><strong>Max Amount:</strong> BDT {{ number_format($filters['amount_max'], 2) }}</p>
        @endif
        @if($filters['search'] ?? null)
            <p><strong>Search:</strong> {{ $filters['search'] }}</p>
        @endif
    </div>
    @endif

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ count($transactions) }}</div>
                <div class="summary-label">Total Transactions</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">BDT {{ number_format($transactions->sum('amount'), 2) }}</div>
                <div class="summary-label">Total Amount</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ $transactions->where('status', 'success')->count() }}</div>
                <div class="summary-label">Successful</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ round(($transactions->where('status', 'success')->count() / max(count($transactions), 1)) * 100, 1) }}%</div>
                <div class="summary-label">Success Rate</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Invoice Number</th>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Transaction Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->transaction_id }}</td>
                <td>{{ $transaction->invoice_number }}</td>
                <td>{{ $transaction->order_number ?: 'N/A' }}</td>
                <td>
                    <strong>{{ $transaction->customer_name }}</strong><br>
                    <small>{{ $transaction->customer_email }}</small><br>
                    <small>{{ $transaction->customer_phone }}</small>
                </td>
                <td class="amount">BDT {{ number_format($transaction->amount, 2) }}</td>
                <td>
                    {{ $transaction->payment_gateway }}<br>
                    <small>{{ $transaction->payment_method ?: 'N/A' }}</small>
                </td>
                <td>
                    <span class="status-{{ $transaction->status }}">
                        {{ ucfirst($transaction->status) }}
                    </span>
                </td>
                <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('M j, Y g:i A') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the system.</p>
        <p>Report generated on {{ date('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>