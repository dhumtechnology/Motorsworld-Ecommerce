@extends('emails.layouts.brand')

@section('title', 'Bienvenido a '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Cuenta creada
    </p>
    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;font-weight:700;color:#111111;">
        ¡Hola, {{ $firstName }}!
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#404040;">
        Tu cuenta en <strong style="color:#111111;">{{ $appName }}</strong> ya está lista.
        Desde ahora puedes comprar repuestos, accesorios y motos, y agendar reservas de servicio en un solo lugar.
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#404040;">
        Registramos tu correo como <strong style="color:#111111;">{{ $email }}</strong>.
        Guárdalo: lo usarás para iniciar sesión.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px;">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $shopUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Ir a la tienda
                </a>
            </td>
            <td width="12" style="font-size:0;line-height:0;">&nbsp;</td>
            <td style="border-radius:4px;border:1px solid #d4d4d4;background-color:#ffffff;">
                <a href="{{ $loginUrl }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#111111;text-decoration:none;">
                    Iniciar sesión
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;line-height:1.6;color:#737373;">
        Si no creaste esta cuenta, puedes ignorar este mensaje. Si necesitas ayuda, responde a este correo o escríbenos desde la tienda.
    </p>
@endsection

@section('aside')
    Estás a un paso de tu próxima moto o servicio.
    Explora el catálogo, arma tu carrito y agenda tu cita cuando lo necesites.
@endsection
