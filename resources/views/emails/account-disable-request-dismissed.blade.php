@extends('emails.layouts.base')

@php $emailTitle = 'Account Disable Request Update – ' . $siteName; @endphp

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

        .headline span { color: #6B4C35; }

        .body-text {
            color: #5A3E2B;
            font-family: Arial, sans-serif;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .reason-box {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-left: 4px solid #9CA3AF;
            border-radius: 4px;
            margin: 24px 0;
            padding: 18px 20px;
        }

        .reason-label {
            color: #4B5563;
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

    <h1 class="headline">
        Account Disable Request<br>
        <span>Not Approved</span>
    </h1>

    <p class="body-text">
        We have reviewed your request to disable your {{ $siteName }} account. At this time, your request has been declined and your account remains active.
    </p>

    @if($adminMessage)
        <div class="reason-box">
            <p class="reason-label">Message from our team</p>
            <p class="reason-text">{{ $adminMessage }}</p>
        </div>
    @endif

    <div class="info-box">
        <p>If you still wish to disable your account, you may submit a new request from your dashboard or contact us at <strong>{{ $contactEmail }}</strong>.</p>
    </div>
@endsection
