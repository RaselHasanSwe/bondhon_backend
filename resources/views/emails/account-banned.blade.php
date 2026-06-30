@extends('emails.layouts.base')

@php $emailTitle = 'Account Suspended – ' . $siteName; @endphp

@section('content')
    <style>
        .ban-headline {
            color: #3D2C1E;
            font-family: 'Georgia', serif;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .ban-headline span { color: #B91C1C; }

        .ban-body-text {
            color: #5A3E2B;
            font-family: Arial, sans-serif;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .reason-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-left: 4px solid #DC2626;
            border-radius: 4px;
            margin: 24px 0;
            padding: 18px 20px;
        }

        .reason-label {
            color: #991B1B;
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .reason-text {
            color: #3D2C1E;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .info-box {
            background-color: #FEF9EE;
            border-left: 3px solid #D4A017;
            border-radius: 0 3px 3px 0;
            margin: 28px 0 0;
            padding: 16px 20px;
        }

        .info-box p {
            color: #6B4C35;
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.7;
            margin: 0;
        }
    </style>

    <p style="color:#3D2C1E;font-family:Georgia,serif;font-size:13px;font-style:italic;margin-bottom:24px;opacity:.7;">
        Assalamu Alaikum / Adaab {{ $user->name }},
    </p>

    <h1 class="ban-headline">
        Your Account Has Been<br>
        <span>Suspended</span>
    </h1>

    <p class="ban-body-text">
        We are writing to inform you that your {{ $siteName }} account has been suspended and you will no longer be able to sign in or use the platform.
    </p>

    <div class="reason-box">
        <p class="reason-label">Reason for suspension</p>
        <p class="reason-text">{{ $reason }}</p>
    </div>

    <div class="info-box">
        <p>If you believe this action was taken in error, please contact our support team at <strong>{{ $contactEmail }}</strong> with your registered email address.</p>
    </div>
@endsection
