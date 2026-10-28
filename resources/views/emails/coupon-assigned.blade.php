@extends('emails.layouts.brand')

@section('title', 'Tienes un cupón disponible — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Cupón asignado
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        ¡Hola, {{ $firstName }}!
    </h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#404040;">
        Tienes un cupón exclusivo disponible en
        <strong style="color:#111111;">{{ $appName }}</strong>.
        Úsalo una sola vez en tu próxima compra.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #e6e6e6;">
        <tr>
            <td style="padding:14px 16px;background-color:#fff8f2;border-bottom:1px solid #ffd7b8;">
                <p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#ff6600;">
                    Código
                </p>
                <p style="margin:0;font-size:24px;font-weight:700;letter-spacing:2px;color:#111111;font-family:Consolas,Monaco,monospace;">
                    {{ $code }}
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    @if ($title)
                        <tr>
                            <td style="padding:0 0 10px;font-size:13px;color:#737373;width:38%;vertical-align:top;">Título</td>
                            <td style="padding:0 0 10px;font-size:14px;color:#111111;font-weight:600;">{{ $title }}</td>
                        </tr>
                    @endif
                    @if ($reason)
                        <tr>
                            <td style="padding:0 0 10px;font-size:13px;color:#737373;vertical-align:top;">Motivo</td>
                            <td style="padding:0 0 10px;font-size:14px;color:#111111;line-height:1.5;">{{ $reason }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Descuento</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;font-weight:600;">{{ $discount }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 {{ count($ruleLines) ? '10px' : '0' }};font-size:13px;color:#737373;">Vigencia</td>
                        <td style="padding:0 0 {{ count($ruleLines) ? '10px' : '0' }};font-size:14px;color:#111111;">
                            {{ $startsAt }} → {{ $endsAt }}
                        </td>
                    </tr>
                    @foreach ($ruleLines as $index => $line)
                        <tr>
                            <td style="padding:0 0 {{ $index < count($ruleLines) - 1 ? '10px' : '0' }};font-size:13px;color:#737373;vertical-align:top;">
                                {{ $line['label'] }}
                            </td>
                            <td style="padding:0 0 {{ $index < count($ruleLines) - 1 ? '10px' : '0' }};font-size:14px;color:#111111;">
                                {{ $line['value'] }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 8px;">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $checkoutUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Usar en el checkout
                </a>
            </td>
            <td width="12" style="font-size:0;line-height:0;">&nbsp;</td>
            <td style="border-radius:4px;border:1px solid #d4d4d4;background-color:#ffffff;">
                <a href="{{ $shopUrl }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#111111;text-decoration:none;">
                    Ir a la tienda
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('aside')
    Ingresa el código <strong>{{ $code }}</strong> en el checkout.
    El cupón es exclusivo para tu cuenta y solo se puede usar una vez.
@endsection
