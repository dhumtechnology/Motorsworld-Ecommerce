@extends('emails.layouts.brand')

@section('title', 'Pedido #'.$orderId.' '.$statusLabel.' — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Actualización de pedido
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        Hola, {{ $firstName }}
    </h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#404040;">
        {{ $statusMessage }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #e6e6e6;">
        <tr>
            <td style="padding:14px 16px;background-color:#fff8f2;border-bottom:1px solid #ffd7b8;">
                <p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#ff6600;">
                    Nuevo estado
                </p>
                <p style="margin:0;font-size:20px;font-weight:700;color:#111111;">
                    {{ $statusLabel }}
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;border-bottom:1px solid #e6e6e6;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;width:38%;">Pedido</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;font-weight:600;">#{{ $orderId }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 {{ $note ? '10px' : '0' }};font-size:13px;color:#737373;">Total</td>
                        <td style="padding:0 0 {{ $note ? '10px' : '0' }};font-size:14px;color:#111111;font-weight:600;">{{ $orderTotal }}</td>
                    </tr>
                    @if ($note)
                        <tr>
                            <td style="padding:0;font-size:13px;color:#737373;vertical-align:top;">Nota</td>
                            <td style="padding:0;font-size:14px;color:#111111;">{{ $note }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;">
                <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#737373;">
                    Productos del pedido
                </p>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    @forelse ($items as $item)
                        <tr>
                            <td style="padding:0 0 12px;font-size:14px;color:#111111;vertical-align:top;">
                                <strong>{{ $item['name'] }}</strong>
                                @if (! empty($item['variant']))
                                    <br><span style="font-size:12px;color:#737373;">{{ $item['variant'] }}</span>
                                @endif
                                <br><span style="font-size:12px;color:#737373;">Cantidad: {{ $item['quantity'] }}</span>
                            </td>
                            <td style="padding:0 0 12px;font-size:14px;color:#111111;font-weight:600;text-align:right;vertical-align:top;white-space:nowrap;">
                                {{ $item['line_total'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td style="padding:0;font-size:14px;color:#737373;">No hay productos en este pedido.</td>
                        </tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $orderUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Ver pedido
                </a>
            </td>
            <td width="12" style="font-size:0;line-height:0;">&nbsp;</td>
            <td style="border-radius:4px;border:1px solid #d4d4d4;background-color:#ffffff;">
                <a href="{{ $accountUrl }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#111111;text-decoration:none;">
                    Mi cuenta
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('aside')
    Si no reconoces este cambio o necesitas ayuda con tu pedido, responde a este correo o contáctanos desde la tienda.
@endsection
