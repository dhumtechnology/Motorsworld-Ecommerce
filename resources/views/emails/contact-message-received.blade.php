@extends('emails.layouts.brand')

@section('title', 'Recibimos tu mensaje — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Contacto
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        Hola, {{ $firstName }}
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#404040;">
        Recibimos tu mensaje en <strong style="color:#111111;">{{ $appName }}</strong>
        con código <strong style="color:#111111;">{{ $code }}</strong>.
        Te responderemos a la brevedad.
    </p>
    <div style="margin:0 0 24px;padding:18px 20px;background-color:#f8f8f8;border:1px solid #e6e6e6;border-radius:4px;">
        <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#737373;">
            Tu mensaje
        </p>
        <p style="margin:0;font-size:15px;line-height:1.7;color:#202020;white-space:pre-wrap;">{{ $customerMessage }}</p>
    </div>

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
    Código de seguimiento: {{ $code }}. Conserva este correo; te responderemos al mismo correo con el que nos escribiste.
@endsection
