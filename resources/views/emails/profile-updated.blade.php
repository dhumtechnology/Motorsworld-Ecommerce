@extends('emails.layouts.brand')

@section('title', 'Datos actualizados — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Seguridad de cuenta
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        Hola, {{ $firstName }}
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#404040;">
        Confirmamos que tus datos de cuenta en <strong style="color:#111111;">{{ $appName }}</strong>
        se actualizaron el <strong style="color:#111111;">{{ $updatedAt }}</strong>.
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#404040;">
        Si fuiste tú, no necesitas hacer nada más. Si no reconoces este cambio,
        inicia sesión y revisa tu cuenta de inmediato.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 8px;">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $accountUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Ver mi cuenta
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('aside')
    Correo asociado: {{ $email }}. El documento de identidad y el correo no se pueden cambiar desde la cuenta.
@endsection
