@php
    $siteName = config('app.name');
    $siteUrl = config('app.url');
    // Real deliveries embed the images as CID attachments (Gmail strips data: URIs);
    // a rendered preview has no message, so it falls back to inline base64.
    $inlineImage = fn (string $file): string => isset($message)
        ? $message->embed(resource_path("images/mail/{$file}"))
        : 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path("images/mail/{$file}")));
    $logoMark = $inlineImage('logo-mark.png');
    $externalIcon = $inlineImage('external-link.png');
@endphp
<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Signika:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0; mso-table-rspace: 0; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f3f5f8; }

        .font { font-family: 'Signika', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        h1, h2, h3, p, td, th, li, a, span, div { font-family: 'Signika', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .body-text { font-size: 15px; line-height: 1.75; color: #374151; }
        .body-text p { margin: 0 0 16px; }
        .body-text a { color: #15803d; }
        .body-text img { max-width: 100%; height: auto; border-radius: 6px; }
        .body-text blockquote { margin: 16px 0; padding-left: 14px; border-left: 3px solid #d7dee8; color: #5f6f86; }
        .body-text ul, .body-text ol { margin: 0 0 16px; padding-left: 22px; }
        .body-text h1, .body-text h2, .body-text h3 { margin: 0 0 12px; line-height: 1.3; color: #090e18; }
        .row-label { font-size: 13px; color: #5f6f86; padding: 6px 12px 6px 0; width: 32%; vertical-align: top; }
        .row-value { font-size: 14px; color: #090e18; font-weight: 600; padding: 6px 0; vertical-align: top; word-break: break-word; }
        .quote { background: #f3f5f8; border-radius: 12px; }
        .break { word-break: break-all; }

        @media only screen and (max-width: 640px) {
            .outer { padding: 12px 8px !important; }
            .card { border-radius: 10px !important; }
            .pad { padding-left: 20px !important; padding-right: 20px !important; }
            .heading { font-size: 20px !important; }
            .btn { display: block !important; text-align: center !important; }
            .row-label, .row-value { display: block !important; width: 100% !important; padding: 2px 0 !important; }
            .row-label { padding-top: 8px !important; }
            .hide-mobile { display: none !important; }
            .pill { padding: 10px 11px !important; }
            .pill-icon { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="font" style="margin:0;padding:0;background-color:#f3f5f8;font-family:'Signika','Helvetica Neue',Helvetica,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;font-size:1px;line-height:1px;color:#f3f5f8;">
        @yield('preheader')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f3f5f8" style="background-color:#f3f5f8;">
        <tr>
            <td align="center" class="outer" style="padding:28px 16px;">
                <table role="presentation" class="card font" width="100%" cellpadding="0" cellspacing="0" style="font-family:'Signika','Helvetica Neue',Helvetica,Arial,sans-serif;max-width:620px;background:#ffffff;border-radius:14px;border:1px solid #e3e8ef;">
                    <tr>
                        <td class="pad" style="padding:22px 32px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="middle" align="left">
                                        <a href="{{ $siteUrl }}" target="_blank" rel="noopener" style="text-decoration:none;color:#090e18;">
                                            <img src="{{ $logoMark }}" alt="" width="36" height="36" style="display:inline-block;vertical-align:middle;width:36px;height:36px;">
                                            <span style="font-family:'Signika','Helvetica Neue',Helvetica,Arial,sans-serif;display:inline-block;vertical-align:middle;margin-left:10px;font-size:17px;font-weight:700;letter-spacing:-0.2px;color:#090e18;">{{ $siteName }}</span>
                                        </a>
                                    </td>
                                    <td valign="middle" align="right" width="1" style="white-space:nowrap;">
                                        <a href="{{ $siteUrl }}" class="pill" target="_blank" rel="noopener" aria-label="{{ __('mail.visit_site') }}" style="font-family:'Signika','Helvetica Neue',Helvetica,Arial,sans-serif;display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-size:12px;font-weight:600;line-height:16px;padding:10px 16px;border-radius:100px;">
                                            <span class="hide-mobile" style="vertical-align:middle;color:#ffffff;">Kunjungi Situs</span>
                                            <img class="pill-icon" src="{{ $externalIcon }}" alt="" width="14" height="14" style="display:inline-block;vertical-align:middle;width:14px;height:14px;margin-left:6px;">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr><td class="pad" style="padding:20px 32px 0;"><hr style="border:0;border-top:1px solid #e3e8ef;margin:0;"></td></tr>

                    <tr>
                        <td class="pad body-text" style="font-family:'Signika','Helvetica Neue',Helvetica,Arial,sans-serif;padding:22px 32px 30px;font-size:15px;line-height:1.75;color:#374151;">
                            <h1 class="heading" style="margin:0 0 14px;font-size:22px;line-height:1.3;font-weight:700;color:#090e18;">@yield('heading')</h1>
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td class="pad" style="padding:22px 32px;background:#f3f5f8;border-top:1px solid #e3e8ef;border-radius:0 0 14px 14px;">
                            <div style="font-family:'Signika','Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#7a879b;text-align:center;">
                                @hasSection('footnote')
                                    <p style="margin:0;">@yield('footnote')</p>
                                @else
                                    <p style="margin:0;">{{ __('mail.footer_auto', ['site' => $siteName]) }}</p>
                                    <p style="margin:6px 0 0;">{{ __('mail.footer_no_reply') }}</p>
                                @endif
                                <p style="margin:12px 0 0;">{!! __('mail.copyright', ['year' => now()->year, 'site' => e($siteName)]) !!}</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
