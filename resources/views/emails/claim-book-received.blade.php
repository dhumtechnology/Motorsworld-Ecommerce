@extends('emails.layouts.brand')

@section('title', 'Confirmación de '.$claimType.' — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Libro de reclamaciones
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        Hola, {{ $firstName }}
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#404040;">
        Confirmamos la recepción de tu <strong style="color:#111111;">{{ strtolower($claimType) }}</strong>
        con código <strong style="color:#111111;">{{ $code }}</strong>.
    </p>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#404040;">
        Lamentamos los inconvenientes ocasionados. En <strong style="color:#111111;">{{ $appName }}</strong>
        tomamos muy en serio cada caso y te atenderemos a la brevedad posible,
        conforme a la normativa vigente de protección al consumidor.
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#404040;">
        Conserva este correo como constancia. Te responderemos al mismo correo
        con el que registraste tu {{ strtolower($claimType) }}.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $shopUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Ir a la tienda
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('aside')
    Código de seguimiento: {{ $code }}. Si necesitas agregar información, responde a este correo mencionando el código.
@endsection
