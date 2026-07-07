<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $subscription->transaction_id }}</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #1F2937;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .page {
            min-height: 100%;
            position: relative;
        }

        /* ── Header ── */
        .header {
            padding: 40px 48px 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-name {
            color: #111827;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.2px;
            margin: 0;
        }

        .brand-tagline {
            color: #9CA3AF;
            font-size: 9.5px;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .invoice-title {
            color: #111827;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: right;
            text-transform: uppercase;
        }

        .invoice-meta {
            margin-top: 8px;
            text-align: right;
        }

        .invoice-meta-row {
            color: #6B7280;
            font-size: 9.5px;
            margin-top: 3px;
        }

        .invoice-meta-row strong {
            color: #374151;
        }

        /* ── Accent rule ── */
        .accent-rule {
            background-color: #A9781F;
            height: 3px;
            margin-top: 24px;
            width: 100%;
        }

        /* ── Body content ── */
        .content {
            padding: 30px 48px 36px;
        }

        /* ── Bill-to block ── */
        .info-table {
            border-collapse: collapse;
            margin-bottom: 30px;
            width: 100%;
        }

        .info-table td {
            vertical-align: top;
            width: 50%;
        }

        .section-label {
            color: #A9781F;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .section-name {
            color: #111827;
            font-size: 13.5px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .section-detail {
            color: #6B7280;
            font-size: 10px;
            line-height: 1.8;
        }

        /* ── Subscription period strip ── */
        .period-strip {
            background-color: #FAFAF9;
            border: 1px solid #E5E7EB;
            border-left: 3px solid #A9781F;
            margin: 0 0 26px;
            padding: 13px 16px;
            width: 100%;
        }

        .period-table {
            border-collapse: collapse;
            width: 100%;
        }

        .period-table td {
            color: #6B7280;
            font-size: 10px;
            text-align: center;
            width: 33%;
        }

        .period-table .p-label {
            color: #9CA3AF;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .period-table .p-value {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
        }

        .period-divider {
            border-left: 1px solid #E5E7EB;
        }

        /* ── Line items table ── */
        .items-table {
            border-collapse: collapse;
            width: 100%;
        }

        .items-table thead tr {
            border-bottom: 2px solid #111827;
        }

        .items-table thead th {
            color: #111827;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1.2px;
            padding: 0 14px 10px;
            text-align: left;
            text-transform: uppercase;
        }

        .items-table thead th:last-child {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #E5E7EB;
        }

        .items-table tbody td {
            color: #1F2937;
            font-size: 11px;
            padding: 14px 14px;
            vertical-align: middle;
        }

        .items-table tbody td:last-child {
            font-weight: bold;
            text-align: right;
        }

        .item-plan {
            font-size: 12px;
            font-weight: bold;
        }

        .item-meta {
            color: #9CA3AF;
            font-size: 9px;
            margin-top: 2px;
        }

        /* ── Totals block ── */
        .totals-wrap {
            width: 100%;
        }

        .totals-table {
            border-collapse: collapse;
            float: right;
            margin-top: 18px;
            width: 46%;
        }

        .totals-table td {
            font-size: 11px;
            padding: 7px 14px;
        }

        .totals-table .t-label {
            color: #6B7280;
            text-align: left;
        }

        .totals-table .t-value {
            color: #1F2937;
            font-weight: bold;
            text-align: right;
        }

        .totals-table .t-divider td {
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
        }

        .totals-table .t-grand td {
            border-top: 2px solid #111827;
            padding-top: 12px;
        }

        .totals-table .t-grand .t-label {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .totals-table .t-grand .t-value {
            color: #A9781F;
            font-size: 18px;
            font-weight: bold;
        }

        .clearfix {
            clear: both;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #E5E7EB;
            margin-top: 30px;
            padding-top: 18px;
            text-align: center;
        }

        .footer-thanks {
            color: #111827;
            font-size: 11.5px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .footer-sub {
            color: #9CA3AF;
            font-size: 9px;
            line-height: 1.7;
        }

        .footer-contact {
            color: #A9781F;
            font-size: 9px;
            margin-top: 7px;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:55%;">
                    <div class="brand-name">{{ $siteName }}</div>
                    <div class="brand-tagline">{{ $siteSlogan ?: 'Matrimony Platform' }}</div>
                </td>
                <td style="width:45%;">
                    <div class="invoice-title">Invoice</div>
                    <div class="invoice-meta">
                        <div class="invoice-meta-row">No. <strong>{{ $subscription->transaction_id }}</strong></div>
                        <div class="invoice-meta-row">Date: <strong>{{ $subscription->created_at?->format('F j, Y') }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div style="padding: 0 48px;">
        <div class="accent-rule"></div>
    </div>

    <div class="content">

        {{-- Bill To / Payment Info --}}
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-label">Billed To</div>
                    <div class="section-name">{{ $user->name }}</div>
                    <div class="section-detail">{{ $user->email }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="section-label">Payment Method</div>
                    <div class="section-detail">{{ $subscription->payment_method }}</div>
                </td>
            </tr>
        </table>

        {{-- Subscription period --}}
        @if($subscription->starts_at || $subscription->expires_at)
            <div class="period-strip">
                <table class="period-table">
                    <tr>
                        <td>
                            <div class="p-label">Valid From</div>
                            <div class="p-value">{{ $subscription->starts_at?->format('M j, Y') ?? '—' }}</div>
                        </td>
                        <td class="period-divider">
                            <div class="p-label">Valid Until</div>
                            <div class="p-value">{{ $subscription->expires_at?->format('M j, Y') ?? '—' }}</div>
                        </td>
                        <td class="period-divider">
                            <div class="p-label">Duration</div>
                            <div class="p-value">{{ $durationLabel !== '—' ? $durationLabel : 'N/A' }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Line items --}}
        <table class="items-table">
            <thead>
            <tr>
                <th style="width:50%;">Description</th>
                <th style="width:20%;">Tier</th>
                <th style="width:15%;">Duration</th>
                <th style="width:15%;">Amount</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <div class="item-plan">{{ $planName }} Subscription</div>
                    <div class="item-meta">Txn: {{ $subscription->transaction_id }}</div>
                </td>
                <td style="text-transform:capitalize;">{{ $planType }}</td>
                <td>{{ $durationLabel !== '—' ? $durationLabel : '—' }}</td>
                <td>BDT {{ $amountFormatted }}</td>
            </tr>
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-wrap">
            <table class="totals-table">
                <tr>
                    <td class="t-label">Subtotal</td>
                    <td class="t-value">BDT {{ $amountFormatted }}</td>
                </tr>
                <tr>
                    <td class="t-label">Tax</td>
                    <td class="t-value">BDT 0</td>
                </tr>
                <tr class="t-divider">
                    <td class="t-label">Discount</td>
                    <td class="t-value">BDT 0</td>
                </tr>
                <tr class="t-grand">
                    <td class="t-label">Total Paid</td>
                    <td class="t-value">BDT {{ $amountFormatted }}</td>
                </tr>
            </table>
            <div class="clearfix"></div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-thanks">Thank you for subscribing to {{ $siteName }}!</div>
            <div class="footer-sub">
                This is a computer-generated invoice and does not require a signature.<br>
                For billing enquiries, please contact our support team.
            </div>
            @if($contactEmail || $contactAddress)
                <div class="footer-contact">
                    @if($contactAddress){{ $contactAddress }}@endif
                    @if($contactAddress && $contactEmail) &nbsp;|&nbsp; @endif
                    @if($contactEmail){{ $contactEmail }}@endif
                </div>
            @endif
        </div>

    </div>
</div>
</body>
</html>