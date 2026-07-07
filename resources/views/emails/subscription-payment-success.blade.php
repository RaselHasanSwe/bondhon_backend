@extends('emails.layouts.base')

@php $emailTitle = 'Payment Confirmed – ' . $siteName; @endphp

@section('content')
    <style>
        .headline {
            color: #3D2C1E;
            font-family: 'Georgia', serif;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .headline span { color: #B8860B; }

        .body-text {
            color: #5A3E2B;
            font-family: Arial, sans-serif;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .detail-box {
            background: #FFFDF5;
            border: 1px solid #E8D5A3;
            border-left: 4px solid #D4A017;
            border-radius: 4px;
            margin: 24px 0;
            padding: 18px 20px;
        }

        .detail-label {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .detail-text {
            color: #3D2C1E;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.7;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 8px;
        }

        .detail-row:last-child { margin-bottom: 0; }

        .detail-key {
            color: #6B4C35;
            font-size: 13px;
        }

        .detail-value {
            color: #3D2C1E;
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }

        .total-box {
            background-color: #FEF9EE;
            border-radius: 6px;
            margin-top: 16px;
            padding: 14px 16px;
            text-align: center;
        }

        .total-label {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .total-amount {
            color: #3D2C1E;
            font-family: 'Georgia', serif;
            font-size: 28px;
            font-weight: 700;
        }

        .cta-wrapper { margin: 30px 0; text-align: center; }

        .cta-button {
            background: linear-gradient(135deg,#B8860B 0%,#D4A017 50%,#B8860B 100%);
            border-radius: 3px;
            box-shadow: 0 4px 20px rgba(184,134,11,0.35);
            color: #fff !important;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2.5px;
            padding: 16px 44px;
            text-decoration: none !important;
            text-transform: uppercase;
        }
    </style>

    <p style="color:#3D2C1E;font-family:Georgia,serif;font-size:13px;font-style:italic;margin-bottom:24px;opacity:.7;">
        Assalamu Alaikum / Adaab {{ $user->name }},
    </p>

    <h1 class="headline">
        Your Payment Is<br>
        <span>Confirmed</span>
    </h1>

    <p class="body-text">
        Thank you for subscribing to {{ $siteName }}. Your <strong>{{ $planName }}</strong> plan is now active.
        A PDF invoice is attached to this email for your records.
    </p>

    <div class="detail-box">
        <p class="detail-label">Payment Summary</p>
        <div class="detail-text">
            <div class="detail-row">
                <span class="detail-key">Plan</span>
                <span class="detail-value">{{ $planName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Tier</span>
                <span class="detail-value" style="text-transform:capitalize;">{{ $planType }}</span>
            </div>
            @if($durationLabel !== '—')
                <div class="detail-row">
                    <span class="detail-key">Duration</span>
                    <span class="detail-value">{{ $durationLabel }}</span>
                </div>
            @endif
            <div class="detail-row">
                <span class="detail-key">Payment Method</span>
                <span class="detail-value">{{ $subscription->payment_method }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Transaction ID</span>
                <span class="detail-value" style="font-family:monospace;font-size:11px;">{{ $subscription->transaction_id }}</span>
            </div>
            @if($subscription->starts_at)
                <div class="detail-row">
                    <span class="detail-key">Valid From</span>
                    <span class="detail-value">{{ $subscription->starts_at->format('M j, Y') }}</span>
                </div>
            @endif
            @if($subscription->expires_at)
                <div class="detail-row">
                    <span class="detail-key">Valid Until</span>
                    <span class="detail-value">{{ $subscription->expires_at->format('M j, Y') }}</span>
                </div>
            @endif
        </div>

        <div class="total-box">
            <div class="total-label">Total Paid</div>
            <div class="total-amount">{{ $amountFormatted }}</div>
        </div>
    </div>

    <div class="cta-wrapper">
        <a href="{{ $subscriptionUrl }}" class="cta-button" target="_blank">
            ✦ &nbsp; View Subscription &nbsp; ✦
        </a>
    </div>

    <p style="color:#9C7050;font-family:Georgia,serif;font-size:13px;font-style:italic;margin-top:28px;text-align:center;">
        Thank you for choosing {{ $siteName }}.<br>
        The {{ $siteName }} Team
    </p>
@endsection
