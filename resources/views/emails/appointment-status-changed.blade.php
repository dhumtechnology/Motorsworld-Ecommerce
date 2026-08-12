@extends('emails.layouts.brand')

@section('title', 'Reserva '.$statusLabel.' — '.$appName)

@section('content')
    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff6600;">
        Actualización de reserva
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
            <td style="padding:16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;width:38%;">Fecha</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;font-weight:600;">{{ $appointmentDate }}</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 10px;font-size:13px;color:#737373;">Hora</td>
                        <td style="padding:0 0 10px;font-size:14px;color:#111111;font-weight:600;">{{ $appointmentTime }}</td>
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
                        <td style="padding:0 0 {{ $cancellationReason ? '10px' : '0' }};font-size:13px;color:#737373;">Placa</td>
                        <td style="padding:0 0 {{ $cancellationReason ? '10px' : '0' }};font-size:14px;color:#111111;font-family:monospace;">{{ $plate }}</td>
                    </tr>
                    @if ($cancellationReason)
                        <tr>
                            <td style="padding:0;font-size:13px;color:#737373;vertical-align:top;">Motivo</td>
                            <td style="padding:0;font-size:14px;color:#111111;">{{ $cancellationReason }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
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
    Si no reconoces este cambio o necesitas ayuda, responde a este correo o contáctanos desde la tienda.
@endsection
