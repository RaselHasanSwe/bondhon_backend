@extends('emails.layouts.base')

@php $emailTitle = 'Verify Your Email – ' . $siteName; @endphp

@section('content')
    <style>
        .verify-greeting {
            color: #3D2C1E;
            font-family: 'Georgia', serif;
            font-size: 13px;
            font-style: italic;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            opacity: 0.7;
        }

        .verify-headline {
            color: #3D2C1E;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .verify-headline span {
            color: #B8860B;
        }

        .section-rule {
            align-items: center;
            display: flex;
            gap: 10px;
            margin: 18px 0 24px;
        }
        .section-rule-line {
            background: linear-gradient(90deg, #D4A017, transparent);
            flex: 1;
            height: 1px;
        }
        .section-rule-gem { color: #D4A017; font-size: 10px; }

        .verify-body-text {
            color: #5A3E2B;
            font-family: Arial, 'Helvetica Neue', sans-serif;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .verify-body-text strong {
            color: #3D2C1E;
            font-weight: 700;
        }

        .otp-wrapper {
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            border: 1px solid #D4A017;
            border-radius: 4px;
            margin: 30px 0;
            padding: 28px 24px;
            text-align: center;
        }

        .otp-label {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 11px;
            letter-spacing: 3px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }

        .otp-code {
            color: #3D2C1E;
            font-family: 'Georgia', serif;
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 12px;
            line-height: 1;
            margin-bottom: 14px;
            text-shadow: 0 2px 8px rgba(184,134,11,0.2);
        }

        .otp-expiry {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .otp-expiry strong {
            color: #B8860B;
        }

        .info-box {
            background-color: #FEF9EE;
            border-left: 3px solid #D4A017;
            border-radius: 0 3px 3px 0;
            margin: 28px 0;
            padding: 16px 20px;
        }

        .info-box p {
            color: #6B4C35;
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.7;
            margin: 0;
        }

        .closing-text {
            border-top: 1px solid #E8D5A3;
            color: #9C7050;
            font-family: 'Georgia', serif;
            font-size: 13px;
            font-style: italic;
            line-height: 1.7;
            margin-top: 32px;
            padding-top: 24px;
            text-align: center;
        }

        .closing-signature {
            color: #6B4C35;
            font-family: 'Georgia', serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .closing-team {
            color: #B8860B;
            font-family: Arial, sans-serif;
            font-size: 11px;
            letter-spacing: 2px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        @media only screen and (max-width: 620px) {
            .verify-headline { font-size: 22px; }
            .otp-code { font-size: 34px; letter-spacing: 8px; }
        }
    </style>

    <p class="verify-greeting">Assalamu Alaikum / Adaab,</p>
    <h1 class="verify-headline">
        Verify Your<br>
        <span>Email Address</span>
    </h1>

    <div class="section-rule">
        <div class="section-rule-line"></div>
        <span class="section-rule-gem">✦</span>
    </div>

    <p class="verify-body-text">
        Welcome to <strong>{{ $siteName }}</strong> — your journey to finding a meaningful life partner begins here.
        To activate your account and start exploring, please verify your email address using the code below.
    </p>

    @if(!empty($otp))
        <div class="otp-wrapper">
            <p class="otp-label">Your Verification Code</p>
            <p class="otp-code">{{ $otp }}</p>
            <p class="otp-expiry">This code expires in <strong>{{ $expiresIn }}</strong></p>
        </div>
    @endif

    <div class="info-box">
        <p>🔒 &nbsp;<strong>Didn't create a {{ $siteName }} account?</strong> You can safely ignore this email — no account will be created without verification.</p>
    </div>

    <div class="closing-text">
        <p>May your search bring you the companionship and joy you truly deserve.</p>
        <p class="closing-signature">Warm regards,</p>
        <p class="closing-team">The {{ $siteName }} Team</p>
    </div>

@endsection
