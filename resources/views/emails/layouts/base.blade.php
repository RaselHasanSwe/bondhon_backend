<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $emailTitle ?? $siteName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* ===== RESET ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        a { text-decoration: none; }

        /* ===== BASE ===== */
        body {
            background-color: #FDF8F0;
            font-family: 'Georgia', 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        /* ===== TOKENS ===== */
        :root {
            --gold-deep:    #B8860B;
            --gold-mid:     #D4A017;
            --gold-bright:  #F0C040;
            --gold-light:   #FDE68A;
            --gold-pale:    #FEF3C7;
            --cream:        #FDF8F0;
            --brown-dark:   #3D2C1E;
            --brown-mid:    #6B4C35;
            --brown-light:  #9C7050;
            --white:        #FFFFFF;
            --divider:      #E8D5A3;
        }

        /* ===== WRAPPER ===== */
        .email-wrapper {
            background-color: #FDF8F0;
            padding: 32px 16px;
            width: 100%;
        }

        .email-container {
            background-color: #FFFFFF;
            border-radius: 4px;
            box-shadow: 0 4px 40px rgba(184,134,11,0.12);
            margin: 0 auto;
            max-width: 600px;
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        .email-header {
            background: linear-gradient(135deg, #3D2C1E 0%, #6B4C35 50%, #3D2C1E 100%);
            padding: 0;
            position: relative;
            text-align: center;
        }

        .header-ornament-top {
            background: linear-gradient(90deg, transparent, #D4A017 20%, #F0C040 50%, #D4A017 80%, transparent);
            height: 3px;
            width: 100%;
        }

        .header-inner {
            padding: 36px 40px 32px;
            position: relative;
        }

        /* Decorative corner elements */
        .header-inner::before,
        .header-inner::after {
            color: #D4A017;
            font-size: 22px;
            opacity: 0.5;
            position: absolute;
            top: 16px;
        }
        .header-inner::before { content: '✦'; left: 20px; }
        .header-inner::after  { content: '✦'; right: 20px; }

        .brand-tagline {
            color: #F0C040;
            font-family: 'Georgia', serif;
            font-size: 10px;
            font-style: italic;
            letter-spacing: 3px;
            margin-bottom: 14px;
            opacity: 0.8;
            text-transform: uppercase;
        }

        .brand-logo-wrap {
            align-items: center;
            display: inline-flex;
            gap: 10px;
            margin-bottom: 8px;
        }

        .brand-icon {
            color: #F0C040;
            font-size: 28px;
            line-height: 1;
        }

        .brand-name {
            color: #F0C040;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 2px;
            line-height: 1;
            text-shadow: 0 2px 12px rgba(240,192,64,0.3);
        }

        .header-divider {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 12px;
        }

        .header-divider-line {
            background: linear-gradient(90deg, transparent, #D4A017);
            flex: 1;
            height: 1px;
            max-width: 80px;
        }
        .header-divider-line.right {
            background: linear-gradient(90deg, #D4A017, transparent);
        }

        .header-divider-gem {
            color: #F0C040;
            font-size: 12px;
        }

        .header-ornament-bottom {
            background: linear-gradient(90deg, transparent, #D4A017 20%, #F0C040 50%, #D4A017 80%, transparent);
            height: 2px;
            opacity: 0.6;
            width: 100%;
        }

        /* ===== BODY ===== */
        .email-body {
            padding: 44px 48px;
        }

        /* ===== FOOTER ===== */
        .email-footer {
            background-color: #FDF8F0;
            border-top: 1px solid #E8D5A3;
            padding: 0;
        }

        .footer-ornament {
            background: linear-gradient(90deg, transparent, #D4A017 30%, #D4A017 70%, transparent);
            height: 1px;
            opacity: 0.4;
        }

        .footer-inner {
            padding: 28px 40px 32px;
            text-align: center;
        }

        .footer-brand {
            color: #9C7050;
            font-family: 'Georgia', serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }

        .footer-tagline {
            color: #B8860B;
            font-family: 'Georgia', serif;
            font-size: 11px;
            font-style: italic;
            letter-spacing: 1px;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 11px;
            letter-spacing: 0.5px;
            margin: 0 10px;
            text-decoration: underline;
        }

        .footer-links a:hover { color: #B8860B; }

        .footer-divider-dots {
            color: #D4A017;
            font-size: 8px;
            letter-spacing: 4px;
            margin-bottom: 16px;
            opacity: 0.6;
        }

        .footer-address {
            color: #B8A080;
            font-family: Arial, sans-serif;
            font-size: 10px;
            letter-spacing: 0.3px;
            line-height: 1.7;
            margin-bottom: 4px;
        }

        .footer-social {
            margin-bottom: 16px;
        }

        .footer-social a {
            color: #B8860B;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin: 0 8px;
            text-decoration: none;
            text-transform: uppercase;
        }

        .footer-social a:hover {
            color: #D4A017;
            text-decoration: underline;
        }

        .footer-unsubscribe {
            color: #C0A870;
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin-top: 12px;
        }

        .footer-unsubscribe a {
            color: #B8860B;
            text-decoration: underline;
        }

        .footer-bottom-bar {
            background: linear-gradient(135deg, #3D2C1E, #6B4C35);
            padding: 10px 40px;
            text-align: center;
        }

        .footer-copyright {
            color: #9C7050;
            font-family: Arial, sans-serif;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        .footer-copyright span {
            color: #D4A017;
        }

        /* ===== RESPONSIVE ===== */
        @media only screen and (max-width: 620px) {
            .email-body  { padding: 32px 24px; }
            .header-inner { padding: 28px 24px 24px; }
            .brand-name  { font-size: 30px; }
            .footer-inner { padding: 24px 20px 28px; }
            .footer-bottom-bar { padding: 10px 20px; }
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <table class="email-container" role="presentation" cellpadding="0" cellspacing="0" width="100%">

        {{-- ═══════════════════ HEADER ═══════════════════ --}}
        <tr>
            <td class="email-header">
                <div class="header-ornament-top"></div>
                <div class="header-inner">
                    <div class="brand-logo-wrap">
                        <span class="brand-icon">♡</span>
                        <span class="brand-name">{{ $siteName }}</span>
                        <span class="brand-icon">♡</span>
                    </div>
                    <p class="brand-tagline">{{ $siteSlogan }}</p>
                    <div class="header-divider">
                        <div class="header-divider-line"></div>
                        <span class="header-divider-gem">✦ ✦ ✦</span>
                        <div class="header-divider-line right"></div>
                    </div>
                </div>
                <div class="header-ornament-bottom"></div>
            </td>
        </tr>

        {{-- ═══════════════════ BODY (child template fills this) ═══════════════════ --}}
        <tr>
            <td class="email-body">
                @yield('content')
            </td>
        </tr>

        {{-- ═══════════════════ FOOTER ═══════════════════ --}}
        <tr>
            <td class="email-footer">
                <div class="footer-ornament"></div>
                <div class="footer-inner">
                    <p class="footer-brand">{{ $siteName }}</p>
                    @if($siteSlogan)
                        <p class="footer-tagline">{{ $siteSlogan }}</p>
                    @endif
                    <div class="footer-links">
                        <a href="{{ config('app.url') }}/privacy">Privacy Policy</a>
                        <a href="{{ config('app.url') }}/terms">Terms of Service</a>
                        <a href="{{ config('app.url') }}/help">Help Center</a>
                        <a href="{{ config('app.url') }}/contact">Contact Us</a>
                    </div>
                    @if(!empty($socialLinks))
                        <div class="footer-social">
                            @foreach($socialLinks as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                    <p class="footer-divider-dots">• • • • • • •</p>
                    <p class="footer-address">
                        {{ $siteName }}
                        @if($contactAddress)
                            &nbsp;|&nbsp; {{ $contactAddress }}
                        @endif
                        @if($contactEmail || $contactPhone)
                            <br>
                            @if($contactEmail){{ $contactEmail }}@endif
                            @if($contactEmail && $contactPhone) &nbsp;|&nbsp; @endif
                            @if($contactPhone){{ $contactPhone }}@endif
                        @endif
                    </p>
                </div>
                <div class="footer-bottom-bar">
                    <p class="footer-copyright">
                        &copy; {{ date('Y') }} <span>{{ $siteName }}</span>. All rights reserved.
                    </p>
                </div>
            </td>
        </tr>

    </table>
</div>
</body>
</html>
