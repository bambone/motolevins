<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;font-size:16px;line-height:1.5;color:#18181b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <tr>
                    <td style="padding:20px 24px;background:#18181b;color:#fafafa;font-weight:700;font-size:18px;">
                        {{ app(\App\Product\Settings\ProductMailSettingsResolver::class)->platformBrandName() }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#fafafa;font-size:13px;color:#71717a;border-top:1px solid #e4e4e7;">
                        Транзакционное письмо по заявке с сайта. При наличии reply-to ответ уйдёт заявителю.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
