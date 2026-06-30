@extends('emails.layouts.base')

@php $emailTitle = 'Your Daily Match Digest – ' . $siteName; @endphp

@section('content')
<style>
    .greeting {
        color: #3D2C1E;
        font-family: 'Georgia', serif;
        font-size: 13px;
        font-style: italic;
        letter-spacing: 0.5px;
        margin-bottom: 24px;
        opacity: 0.7;
    }
    .headline {
        color: #3D2C1E;
        font-family: 'Georgia', 'Times New Roman', serif;
        font-size: 26px;
        font-weight: 700;
        letter-spacing: 0.5px;
        line-height: 1.3;
        margin-bottom: 6px;
    }
    .headline span { color: #B8860B; }
    .section-rule { align-items: center; display: flex; gap: 10px; margin: 18px 0 24px; }
    .section-rule-line { background: linear-gradient(90deg,#D4A017,transparent); flex: 1; height: 1px; }
    .section-rule-gem { color: #D4A017; font-size: 10px; }
    .body-text {
        color: #5A3E2B;
        font-family: Arial, 'Helvetica Neue', sans-serif;
        font-size: 15px;
        line-height: 1.8;
        margin-bottom: 24px;
    }
    .match-card {
        background: #FFFDF5;
        border: 1px solid #E8D5A3;
        border-left: 4px solid #D4A017;
        border-radius: 6px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .match-card-inner {
        align-items: center;
        display: flex;
        gap: 20px;
        padding: 20px 22px;
    }
    .match-photo {
        border: 2px solid #E8D5A3;
        border-radius: 50%;
        flex-shrink: 0;
        height: 72px;
        object-fit: cover;
        width: 72px;
    }
    .match-photo-placeholder {
        align-items: center;
        background: linear-gradient(135deg, #FEF3C7, #FDE68A);
        border: 2px solid #E8D5A3;
        border-radius: 50%;
        color: #B8860B;
        display: flex;
        flex-shrink: 0;
        font-family: 'Georgia', serif;
        font-size: 28px;
        font-weight: 700;
        height: 72px;
        justify-content: center;
        width: 72px;
    }
    .match-header {
        align-items: flex-start;
        display: flex;
        flex-wrap: wrap;
        gap: 8px 12px;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .match-name {
        color: #3D2C1E;
        font-family: 'Georgia', serif;
        font-size: 20px;
        font-weight: 700;
    }
    .match-score-badge {
        background: linear-gradient(135deg, #B8860B 0%, #D4A017 100%);
        border-radius: 20px;
        color: #fff;
        font-family: Arial, sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 5px 12px;
        white-space: nowrap;
    }
    .match-meta {
        color: #6B4C35;
        font-family: Arial, sans-serif;
        font-size: 13px;
        line-height: 1.7;
    }
    .match-cta {
        display: inline-block;
        background: linear-gradient(135deg,#B8860B 0%,#D4A017 50%,#B8860B 100%);
        border-radius: 3px;
        color: #fff !important;
        font-family: Arial, sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin-top: 10px;
        padding: 9px 22px;
        text-decoration: none !important;
        text-transform: uppercase;
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
    .closing-signature { color: #6B4C35; font-family: 'Georgia', serif; font-size: 14px; font-weight: 700; letter-spacing: 1px; margin-top: 10px; }
    .closing-team { color: #B8860B; font-family: Arial, sans-serif; font-size: 11px; letter-spacing: 2px; margin-top: 4px; text-transform: uppercase; }
    @media only screen and (max-width: 620px) {
        .headline { font-size: 22px; }
        .match-card-inner { flex-direction: column; text-align: center; }
        .cta-button { padding: 14px 28px; font-size: 13px; }
    }
</style>

<p class="greeting">Assalamu Alaikum / Adaab, {{ $user->name ?? 'Dear Member' }},</p>

<h1 class="headline">
    Your Daily<br><span>Match Digest</span>
</h1>

<div class="section-rule">
    <div class="section-rule-line"></div>
    <span class="section-rule-gem">✦</span>
</div>

<p class="body-text">
    Great news! We found <strong>{{ count($topMatches) }} highly compatible profile{{ count($topMatches) !== 1 ? 's' : '' }}</strong> for you today
    based on your partner preferences, religion, family values, lifestyle, and more.
</p>

@forelse($topMatches as $match)
<div class="match-card">
    <div class="match-card-inner">
        @if(!empty($match['photo_url']))
            <img src="{{ $match['photo_url'] }}" alt="{{ $match['name'] }}" class="match-photo">
        @else
            <div class="match-photo-placeholder">{{ strtoupper(substr($match['name'], 0, 1)) }}</div>
        @endif

        <div style="flex:1; min-width:0;">
            <div class="match-header">
                <span class="match-name">{{ $match['name'] }}</span>
                <span class="match-score-badge">{{ $match['score'] }}% Match</span>
            </div>
            <div class="match-meta">
                @if(!empty($match['age']))
                    {{ $match['age'] }} years &nbsp;&nbsp;
                @endif
                @if(!empty($match['city']) || !empty($match['country']))
                    📍
                    @if(!empty($match['city']))
                        {{ ucfirst($match['city']) }}
                    @endif
                    @if(!empty($match['state']))
                        , {{ ucfirst($match['state']) }}
                    @endif
                    @if(!empty($match['country']))
                        , {{ ucfirst(str_replace('_', ' ', $match['country'])) }}
                    @endif
                    <br>
                @endif
                @if(!empty($match['religion']))
                    🕌 {{ ucfirst($match['religion']) }}&nbsp;&nbsp;
                @endif
                @if(!empty($match['education']))
                    🎓 {{ ucwords(str_replace('_', ' ', $match['education'])) }}
                @endif
                @if(!empty($match['profession']))
                    <br>💼 {{ ucwords(str_replace('_', ' ', $match['profession'])) }}
                @endif
            </div>
            <a href="{{ $match['profile_url'] }}" class="match-cta" target="_blank">View Profile ›</a>
        </div>
    </div>
</div>
@empty
<p class="body-text">No new high-compatibility matches were found today. Complete your profile and preferences to get better matches!</p>
@endforelse

<div class="cta-wrapper">
    <a href="{{ $matchesUrl }}" class="cta-button" target="_blank">
        ✦ &nbsp; View All Matches &nbsp; ✦
    </a>
</div>

<div class="closing-text">
    <p>May you find the companionship and joy you truly deserve.</p>
    <p class="closing-signature">Warm regards,</p>
    <p class="closing-team">The {{ $siteName }} Team</p>
</div>
@endsection
