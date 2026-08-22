@extends('emails.layouts.brand')

@section('title', 'Confirmar contraseña — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Confirmación requerida
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        Hola, {{ $firstName }}
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#404040;">
        Recibimos una solicitud para cambiar la contraseña de tu cuenta en
        <strong style="color:#111111;">{{ $appName }}</strong>.
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#404040;">
        Para completar el cambio, confirma con el botón de abajo.
        El enlace vence en 60 minutos. Si no solicitaste esto, ignora este correo
        y tu contraseña actual seguirá activa.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px;">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $confirmUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Confirmar cambio de contraseña
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;line-height:1.6;color:#737373;">
        Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
        <a href="{{ $confirmUrl }}" style="color:#ff6600;word-break:break-all;text-decoration:none;">{{ $confirmUrl }}</a>
    </p>
@endsection

@section('aside')
    Por tu seguridad, nunca compartas este correo ni el enlace con nadie.
@endsection
