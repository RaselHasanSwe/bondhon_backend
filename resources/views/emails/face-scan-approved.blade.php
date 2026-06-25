@extends('emails.layouts.base')

@php $emailTitle = 'Face Verification Approved – MyBouma'; @endphp

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

        .headline span { color: #15803D; }

        .body-text {
            color: #5A3E2B;
            font-family: Arial, sans-serif;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .info-box {
            background-color: #F0FDF4;
            border-left: 3px solid #16A34A;
            border-radius: 0 3px 3px 0;
            margin: 28px 0 0;
            padding: 16px 20px;
        }

        .info-box p {
            color: #166534;
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
        Your Face Verification Has Been<br>
        <span>Approved</span>
    </h1>

    <p class="body-text">
        Great news! Your face scan verification has been reviewed and approved. Your account is now fully active and you can access all features of MyBouma.
    </p>

    <div class="info-box">
        <p>You can now log in and start exploring matches, sending interests, and connecting with others on the platform.</p>
    </div>
@endsection
