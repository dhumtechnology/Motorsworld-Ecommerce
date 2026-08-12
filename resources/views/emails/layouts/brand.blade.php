<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name', 'Motoworld'))</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#202020;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f5;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background-color:#ffffff;border:1px solid #e6e6e6;">
                    <tr>
                        <td style="background-color:#111111;padding:28px 32px;text-align:center;">
                            @if (! empty($logoPath) && is_file($logoPath))
                                <img src="{{ $message->embed($logoPath) }}" alt="{{ $appName ?? 'Motoworld' }}" width="180" style="display:inline-block;max-width:180px;height:auto;border:0;">
                            @else
                                <span style="font-size:22px;font-weight:700;letter-spacing:2px;color:#ffffff;text-transform:uppercase;">{{ $appName ?? 'Motoworld' }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px;background-color:#ff6600;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 28px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fff1e6;border:1px solid #ffd7b8;">
                                <tr>
                                    <td style="padding:16px 18px;font-size:13px;line-height:1.5;color:#404040;">
                                        @yield('aside')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8f8f8;border-top:1px solid #e6e6e6;padding:20px 32px;text-align:center;">
                            <p style="margin:0 0 6px;font-size:12px;color:#737373;">
                                © {{ date('Y') }} {{ $appName ?? 'Motoworld' }}. Todos los derechos reservados.
                            </p>
                            <p style="margin:0;font-size:12px;color:#737373;">
                                <a href="{{ $shopUrl ?? config('app.url') }}" style="color:#ff6600;text-decoration:none;">{{ parse_url((string) ($shopUrl ?? config('app.url')), PHP_URL_HOST) ?: 'motoworld.pe' }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
