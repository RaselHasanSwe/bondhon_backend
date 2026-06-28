@extends('emails.layouts.base')

@php $emailTitle = 'Account Disable Request Received – ' . $siteName; @endphp

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

        .info-box {
            background-color: #FEF9EE;
            border-left: 3px solid #D4A017;
            border-radius: 0 3px 3px 0;
            margin: 24px 0 0;
            padding: 16px 20px;
        }

        .info-box p {
            color: #6B4C35;
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.7;
            margin: 0;
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
    </style>

    <p style="color:#3D2C1E;font-family:Georgia,serif;font-size:13px;font-style:italic;margin-bottom:24px;opacity:.7;">
        Assalamu Alaikum / Adaab {{ $user->name }},
    </p>

    <h1 class="headline">
        We Received Your<br>
        <span>Account Disable Request</span>
    </h1>

    <p class="body-text">
        Thank you for contacting us. Your request to disable your {{ $siteName }} account has been received and is now under review by our admin team.
    </p>

    @if($requestTypeLabel)
        <div class="detail-box">
            <p class="detail-label">Request Type</p>
            <p class="detail-text">{{ $requestTypeLabel }}</p>
        </div>
    @endif

    <div class="info-box">
        <p>Your account remains active until an administrator reviews your request. You will receive another notification once a decision has been made.</p>
    </div>

    <p style="color:#9C7050;font-family:Georgia,serif;font-size:13px;font-style:italic;margin-top:28px;">
        The {{ $siteName }} Team
    </p>
@endsection
