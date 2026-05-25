@php
    $cliente = $factura->socio?->persona?->nombre_completo ?? 'Cliente EPSAS';
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->numero_factura }}</title>
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px;background:#fff7ed;border-bottom:1px solid #fed7aa;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:800;letter-spacing:3px;color:#ea580c;text-transform:uppercase;">EPSAS</p>
                            <h1 style="margin:0;font-size:26px;line-height:1.2;color:#0f172a;">Factura adjunta</h1>
                            <p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:#475569;">Hola {{ $cliente }}, adjuntamos tu factura {{ $factura->numero_factura }} en formato PDF.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0;font-size:15px;line-height:1.7;color:#475569;">Total facturado: <strong>Bs {{ number_format((float) $factura->total, 2) }}</strong></p>
                            <p style="margin:12px 0 0;font-size:14px;line-height:1.7;color:#64748b;">Este correo contiene el PDF como archivo adjunto para que puedas descargarlo o conservarlo.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
