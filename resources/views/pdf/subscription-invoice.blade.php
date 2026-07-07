<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $subscription->transaction_id }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 32px;
        }

        .header {
            margin-bottom: 24px;
            overflow: hidden;
        }

        .brand {
            float: left;
            width: 55%;
        }

        .brand-name {
            color: #C9A227;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .brand-tagline {
            color: #6B7280;
            font-size: 11px;
        }

        .invoice-meta {
            float: right;
            text-align: right;
            width: 40%;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .invoice-id {
            color: #374151;
            font-family: monospace;
            font-size: 11px;
            word-break: break-all;
        }

        .divider {
            border-top: 1px solid #E5E7EB;
            clear: both;
            margin: 20px 0;
        }

        table {
            border-collapse: collapse;
            margin: 16px 0;
            width: 100%;
        }

        th, td {
            border: 1px solid #E5E7EB;
            font-size: 12px;
            padding: 8px 12px;
            vertical-align: top;
        }

        th {
            background: #F9FAFB;
            font-weight: 600;
            text-align: left;
        }

        .label {
            color: #6B7280;
            width: 36%;
        }

        .total-box {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-top: 20px;
            overflow: hidden;
            padding: 16px;
        }

        .total-row {
            overflow: hidden;
        }

        .total-label {
            color: #4B5563;
            float: left;
            font-size: 13px;
            font-weight: 600;
        }

        .total-amount {
            float: right;
            font-size: 22px;
            font-weight: bold;
        }

        .footer {
            color: #9CA3AF;
            font-size: 10px;
            margin-top: 28px;
            text-align: center;
        }

        .status {
            display: inline-block;
            font-weight: 600;
            text-transform: capitalize;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div class="brand-name">{{ $siteName }}</div>
            <div class="brand-tagline">{{ $siteSlogan ?: 'Matrimony Platform' }}</div>
            @if($contactAddress)
                <div style="margin-top:8px;color:#6B7280;font-size:11px;">{{ $contactAddress }}</div>
            @endif
            @if($contactEmail)
                <div style="color:#6B7280;font-size:11px;">{{ $contactEmail }}</div>
            @endif
        </div>

        <div class="invoice-meta">
            <div class="invoice-title">Invoice</div>
            <div class="invoice-id">#{{ $subscription->transaction_id }}</div>
            <div style="margin-top:6px;color:#6B7280;">
                {{ $subscription->created_at?->format('M j, Y') }}
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <table>
        <tbody>
            <tr>
                <th class="label">Customer</th>
                <td>{{ $user->name }}<br>{{ $user->email }}</td>
            </tr>
            <tr>
                <th class="label">Plan</th>
                <td>{{ $planName }}</td>
            </tr>
            <tr>
                <th class="label">Tier</th>
                <td style="text-transform:capitalize;">{{ $planType }}</td>
            </tr>
            @if($durationLabel !== '—')
                <tr>
                    <th class="label">Duration</th>
                    <td>{{ $durationLabel }}</td>
                </tr>
            @endif
            <tr>
                <th class="label">Payment Method</th>
                <td>{{ $subscription->payment_method }}</td>
            </tr>
            <tr>
                <th class="label">Transaction ID</th>
                <td style="font-family:monospace;font-size:11px;">{{ $subscription->transaction_id }}</td>
            </tr>
            <tr>
                <th class="label">Status</th>
                <td><span class="status">{{ $subscription->status }}</span></td>
            </tr>
            @if($subscription->starts_at)
                <tr>
                    <th class="label">Valid From</th>
                    <td>{{ $subscription->starts_at->format('M j, Y') }}</td>
                </tr>
            @endif
            @if($subscription->expires_at)
                <tr>
                    <th class="label">Valid Until</th>
                    <td>{{ $subscription->expires_at->format('M j, Y') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-row">
            <span class="total-label">Total Paid</span>
            <span class="total-amount">{{ $amountFormatted }}</span>
        </div>
    </div>

    <div class="footer">
        Thank you for subscribing to {{ $siteName }} Premium!
    </div>
</body>
</html>
