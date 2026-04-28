@extends('emails.layouts.base')

@php $emailTitle = 'Reset Your Password – Bondhon'; @endphp

@section('content')
    <style>
        .reset-greeting {
            color: #3D2C1E;
            font-family: 'Georgia', serif;
            font-size: 13px;
            font-style: italic;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            opacity: 0.7;
        }

        .reset-headline {
            color: #3D2C1E;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .reset-headline span { color: #B8860B; }

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

        .reset-body-text {
            color: #5A3E2B;
            font-family: Arial, 'Helvetica Neue', sans-serif;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .reset-body-text strong {
            color: #3D2C1E;
            font-weight: 700;
        }

        .cta-wrapper {
            margin: 36px 0;
            text-align: center;
        }

        .cta-button {
            background: linear-gradient(135deg, #B8860B 0%, #D4A017 50%, #B8860B 100%);
            border-radius: 3px;
            box-shadow: 0 4px 20px rgba(184,134,11,0.35), inset 0 1px 0 rgba(255,255,255,0.2);
            color: #FFFFFF !important;
            display: inline-block;
            font-family: Arial, 'Helvetica Neue', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2.5px;
            padding: 16px 44px;
            text-align: center;
            text-decoration: none !important;
            text-transform: uppercase;
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

        .info-box p + p { margin-top: 6px; }

        .url-fallback { margin: 24px 0 0; }

        .url-fallback-label {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 12px;
            letter-spacing: 0.3px;
            margin-bottom: 8px;
        }

        .url-box {
            background-color: #F9F4EA;
            border: 1px solid #E8D5A3;
            border-radius: 3px;
            padding: 12px 16px;
            word-break: break-all;
        }

        .url-box a {
            color: #B8860B;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            text-decoration: underline;
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
            .reset-headline { font-size: 22px; }
            .cta-button { padding: 14px 28px; font-size: 13px; }
        }
    </style>

    <p class="reset-greeting">Assalamu Alaikum / Adaab,</p>
    <h1 class="reset-headline">
        Reset Your<br>
        <span>Password</span>
    </h1>

    <div class="section-rule">
        <div class="section-rule-line"></div>
        <span class="section-rule-gem">✦</span>
    </div>

    <p class="reset-body-text">
        We received a request to reset the password for your <strong>Bondhon</strong> account.
        Click the button below to choose a new password.
    </p>

    <div class="cta-wrapper">
        <a href="{{ $url }}" class="cta-button" target="_blank">
            ✦ &nbsp; Reset My Password &nbsp; ✦
        </a>
    </div>

    <div class="info-box">
        <p>🔒 &nbsp;<strong>Didn't request a password reset?</strong> You can safely ignore this email — your password will not be changed.</p>
        <p>⏰ &nbsp;This link will expire in <strong>60 minutes</strong>.</p>
    </div>

    <div class="url-fallback">
        <p class="url-fallback-label">If the button doesn't work, copy and paste this link into your browser:</p>
        <div class="url-box">
            <a href="{{ $url }}" target="_blank">{{ $url }}</a>
        </div>
    </div>

    <div class="closing-text">
        <p>Your privacy and security are our highest priority.</p>
        <p class="closing-signature">Warm regards,</p>
        <p class="closing-team">The Bondhon Team</p>
    </div>

@endsection

