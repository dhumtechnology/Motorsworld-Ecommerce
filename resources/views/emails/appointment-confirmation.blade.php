@extends('emails.layouts.brand')

@section('title', 'Confirmación de reserva — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Reserva de servicio
    </p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:700;color:#111111;">
        ¡Hola, {{ $firstName }}!
    </h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#404040;">
        Recibimos tu reserva en <strong style="color:#111111;">{{ $appName }}</strong>.
        Conserva este correo con el detalle de tu cita.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #e6e6e6;">
        <tr>
            <td style="padding:14px 16px;background-color:#fff8f2;border-bottom:1px solid #ffd7b8;">
                <p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#ff6600;">
                    Fecha y hora
                </p>
                <p style="margin:0;font-size:20px;font-weight:700;color:#111111;">
                    {{ $appointmentDate }} · {{ $appointmentTime }}
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;width:38%;">Cliente</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;font-weight:600;">{{ $customerName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Correo</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;">{{ $customerEmail }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Documento</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;">{{ $customerDocument }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Teléfono</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;">{{ $customerPhone }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Servicio</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;">{{ $serviceType }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Paquete</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;">{{ $servicePackage }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Vehículo</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;">{{ $vehicle }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 {{ $km || $comments ? '10px' : '0' }};font-size:13px;color:#737373;">Placa</td>
                        <td style="padding:0 0 {{ $km || $comments ? '10px' : '0' }};font-size:14px;color:#111111;font-family:monospace;">{{ $plate }}</td>
                    </tr>
                    @if ($km)
                        <tr>
                            <td style="padding:0 0 {{ $comments ? '10px' : '0' }};font-size:13px;color:#737373;">Kilometraje</td>
                            <td style="padding:0 0 {{ $comments ? '10px' : '0' }};font-size:14px;color:#111111;">{{ $km }} km</td>
                        </tr>
                    @endif
                    @if ($comments)
                        <tr>
                            <td style="padding:0;font-size:13px;color:#737373;vertical-align:top;">Comentarios</td>
                            <td style="padding:0;font-size:14px;color:#111111;">{{ $comments }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#404040;">
        Tu reserva quedó registrada como <strong style="color:#111111;">pendiente</strong>.
        Te contactaremos si necesitamos confirmar algún detalle.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="border-radius:4px;background-color:#ff6600;">
                <a href="{{ $servicesUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#ffffff;text-decoration:none;">
                    Ver servicios
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('aside')
    Si no solicitaste esta reserva o necesitas cambiar la fecha, responde a este correo o contáctanos desde la tienda.
@endsection
