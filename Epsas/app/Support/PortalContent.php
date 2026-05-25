<?php

namespace App\Support;

class PortalContent
{
    public static function defaults(): array
    {
        return [
            'home_kicker' => 'Portal ciudadano',
            'home_title' => 'EPSAS El Portillo Pagos QR.',
            'home_intro' => 'Informacion publica, comunicados, proyectos y orientacion de pagos por orden QR para socios del servicio de agua potable.',
            'home_video_url' => 'https://cdn.pixabay.com/video/2020/05/26/40252-425831511_large.mp4',
            'home_video_path' => null,
            'home_image_1' => null,
            'home_image_2' => null,
            'home_image_3' => null,
            'featured_title' => 'Portal publico con noticias, imagenes y acceso ordenado a pagos.',
            'featured_text' => 'El inicio presenta la identidad del servicio, informacion ciudadana y contenido visual. El modulo de deuda vive en Pagos online para que el usuario sepa exactamente donde iniciar el proceso.',
            'schedule_summary' => 'Lunes a viernes de 08:00 a 16:00. Consulta comunicados por feriados o mantenimientos.',
            'payment_note' => 'El pago se realiza por orden especifica y se verifica antes de marcar facturas como pagadas.',
            'pages' => self::defaultPages(),
        ];
    }

    public static function merge(?array $stored): array
    {
        $stored = is_array($stored) ? $stored : [];
        $content = array_replace_recursive(self::defaults(), $stored);

        foreach (self::defaultPages() as $key => $page) {
            $content['pages'][$key] = array_replace_recursive($page, $content['pages'][$key] ?? []);
            $content['pages'][$key]['cards'] = self::normalizeCards($content['pages'][$key]['cards'] ?? $page['cards']);
            $content['pages'][$key]['bullets'] = self::normalizeBullets($content['pages'][$key]['bullets'] ?? $page['bullets']);
        }

        return $content;
    }

    public static function cardsToText(array $cards): string
    {
        return collect(self::normalizeCards($cards))
            ->map(fn (array $card) => trim(($card['title'] ?? '') . ' | ' . ($card['text'] ?? '')))
            ->filter()
            ->implode("\n");
    }

    public static function bulletsToText(array $bullets): string
    {
        return collect(self::normalizeBullets($bullets))
            ->filter()
            ->implode("\n");
    }

    public static function textToCards(?string $text, array $fallback = []): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text);
        $cards = collect($lines)
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                [$title, $body] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

                return [
                    'title' => $title,
                    'text' => $body,
                ];
            })
            ->filter(fn (array $card) => $card['title'] !== '' || $card['text'] !== '')
            ->values()
            ->all();

        return $cards !== [] ? $cards : self::normalizeCards($fallback);
    }

    public static function textToBullets(?string $text, array $fallback = []): array
    {
        $bullets = collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return $bullets !== [] ? $bullets : self::normalizeBullets($fallback);
    }

    public static function defaultPages(): array
    {
        return [
            'sobre-nosotros' => [
                'kicker' => 'Sobre nosotros',
                'title' => 'Cuidamos el servicio de agua con informacion clara y trabajo de campo.',
                'intro' => 'EPSAS El Portillo centraliza lecturaciones, facturacion, pagos, cortes, reconexiones e incidencias para que cada socio tenga trazabilidad de su servicio.',
                'hero' => 'Gestion operativa',
                'cards' => [
                    ['title' => 'Mision', 'text' => 'Asegurar un servicio continuo, transparente y organizado para la comunidad.'],
                    ['title' => 'Vision', 'text' => 'Digitalizar la atencion ciudadana sin perder el trato cercano.'],
                    ['title' => 'Compromiso', 'text' => 'Registrar cada lectura, pago y solicitud con datos verificables.'],
                ],
                'bullets' => ['Lecturaciones mensuales', 'Facturacion por periodos reales', 'Seguimiento de cortes y reconexiones', 'Reportes administrativos'],
            ],
            'atencion-al-publico' => [
                'kicker' => 'Atencion al publico',
                'title' => 'Canales simples para resolver consultas, pagos y reclamos.',
                'intro' => 'La atencion al socio se organiza por prioridad: pagos y deuda, consultas de medidor, reclamos por consumo, solicitudes tecnicas y actualizacion de datos.',
                'hero' => 'Mesa de ayuda',
                'cards' => [
                    ['title' => 'Caja y pagos', 'text' => 'Revision de saldos, ordenes QR y comprobantes.'],
                    ['title' => 'Soporte tecnico', 'text' => 'Incidencias, fugas, cortes, reconexiones e instalaciones.'],
                    ['title' => 'Actualizacion', 'text' => 'Datos de contacto, direccion, correo y telefono.'],
                ],
                'bullets' => ['Trae tu numero de socio', 'Verifica tu medidor activo', 'Conserva el comprobante', 'Revisa comunicados antes de reclamar'],
            ],
            'comunicados' => [
                'kicker' => 'Comunicados',
                'title' => 'Avisos importantes para socios y vecinos.',
                'intro' => 'En esta seccion se publican cortes programados, mantenimientos, cambios de horario, campanas de regularizacion y novedades operativas.',
                'hero' => 'Avisos vigentes',
                'cards' => [
                    ['title' => 'Mantenimiento preventivo', 'text' => 'Revisa fechas de trabajo por zona antes de reportar baja presion.'],
                    ['title' => 'Regularizacion de deuda', 'text' => 'Los pagos se realizan desde la deuda mas antigua hacia adelante.'],
                    ['title' => 'Comprobantes QR', 'text' => 'Sube comprobantes legibles para acelerar la aprobacion administrativa.'],
                ],
                'bullets' => ['Cortes programados', 'Alertas por zona', 'Avisos de caja', 'Campanas comunitarias'],
            ],
            'horarios' => [
                'kicker' => 'Horarios',
                'title' => 'Horarios de atencion y ventanas operativas.',
                'intro' => 'Consulta los horarios sugeridos para caja, atencion al socio, soporte tecnico y emergencias reportadas por el portal.',
                'hero' => 'Agenda semanal',
                'cards' => [
                    ['title' => 'Atencion general', 'text' => 'Lunes a viernes de 08:00 a 16:00.'],
                    ['title' => 'Caja', 'text' => 'Pagos presenciales de 08:30 a 15:30.'],
                    ['title' => 'Emergencias', 'text' => 'Reportes prioritarios segun disponibilidad del equipo tecnico.'],
                ],
                'bullets' => ['Evita filas consultando en linea', 'Los domingos se consideran dias libres', 'Verifica comunicados por feriados', 'Guarda tu orden QR'],
            ],
            'proyectos' => [
                'kicker' => 'Proyectos',
                'title' => 'Mejoras de red, instalaciones y mantenimiento planificado.',
                'intro' => 'La empresa registra trabajos de campo, instalaciones nuevas, cambios de medidor, incidencias y necesidades de materiales para reportes administrativos.',
                'hero' => 'Obras y red',
                'cards' => [
                    ['title' => 'Instalaciones nuevas', 'text' => 'Coordenadas, zona, sector y fecha quedan registradas.'],
                    ['title' => 'Mantenimiento de red', 'text' => 'Materiales y gastos se reportan para revision administrativa.'],
                    ['title' => 'Control de medidores', 'text' => 'Seguimiento de reemplazos, danos y lecturas atipicas.'],
                ],
                'bullets' => ['Ampliacion de cobertura', 'Control de fugas', 'Planificacion por zona', 'Trazabilidad tecnica'],
            ],
            'pagos-online' => [
                'kicker' => 'Pagos online',
                'title' => 'Paga con una orden especifica y evita inconsistencias.',
                'intro' => 'El sistema no deja pagar meses recientes si existen meses antiguos pendientes. Selecciona hasta que factura pagar y el portal armara la orden completa.',
                'hero' => 'Orden QR segura',
                'cards' => [
                    ['title' => 'Seleccion secuencial', 'text' => 'Si eliges marzo, se incluyen enero y febrero si siguen abiertos.'],
                    ['title' => 'Monto protegido', 'text' => 'El total lo calcula el sistema y no puede editarse manualmente.'],
                    ['title' => 'Revision administrativa', 'text' => 'El comprobante se aprueba antes de marcar facturas pagadas.'],
                ],
                'bullets' => ['Consulta tu codigo', 'Elige hasta que mes pagar', 'Escanea el QR de prueba', 'Sube comprobante legible'],
            ],
            'puntos' => [
                'kicker' => 'Puntos de pago',
                'title' => 'Opciones disponibles para cancelar tu servicio.',
                'intro' => 'Puedes pagar mediante orden QR en el portal y registrar la entidad financiera usada. Para atencion presencial, lleva tu numero de socio.',
                'hero' => 'Puntos y canales',
                'cards' => [
                    ['title' => 'QR empresa', 'text' => 'Disponible para pruebas y validacion interna.'],
                    ['title' => 'Caja administrativa', 'text' => 'Pagos revisados por personal autorizado.'],
                    ['title' => 'Bancos y billeteras', 'text' => 'Selecciona la entidad al subir el comprobante.'],
                ],
                'bullets' => ['Banco Union', 'BNB', 'Mercantil Santa Cruz', 'BISA', 'Billeteras disponibles'],
            ],
            'epsas-informa' => [
                'kicker' => 'EPSAS informa',
                'title' => 'Informacion util para cuidar el agua y entender tu factura.',
                'intro' => 'Publicamos recomendaciones, explicaciones sobre cargos, consumo responsable, medidores y procesos de regularizacion.',
                'hero' => 'Guia ciudadana',
                'cards' => [
                    ['title' => 'Cargo fijo', 'text' => 'Aunque no exista consumo alto, el servicio mantiene un cargo base.'],
                    ['title' => 'Lectura mensual', 'text' => 'La lectura actual representa lo que marca el medidor en campo.'],
                    ['title' => 'Anomalias', 'text' => 'Medidores danados o manipulados deben reportarse para revision.'],
                ],
                'bullets' => ['Ahorro de agua', 'Lectura responsable', 'Reportes por fuga', 'Uso correcto del portal'],
            ],
            'contactanos' => [
                'kicker' => 'Contactanos',
                'title' => 'Estamos para ayudarte con consultas, reclamos y pagos.',
                'intro' => 'Usa los datos de contacto institucionales para consultar deuda, solicitar soporte o revisar un comprobante observado.',
                'hero' => 'Contacto directo',
                'cards' => [
                    ['title' => 'Telefono', 'text' => 'Comunicate con atencion al socio durante horario laboral.'],
                    ['title' => 'Correo', 'text' => 'Solicita informacion o actualizacion de datos.'],
                    ['title' => 'Oficina', 'text' => 'Lleva tu numero de socio para atencion rapida.'],
                ],
                'bullets' => ['Consultas de deuda', 'Soporte de comprobantes', 'Reclamos de lectura', 'Actualizacion de datos'],
            ],
        ];
    }

    private static function normalizeCards(array $cards): array
    {
        return collect($cards)
            ->map(fn ($card) => is_array($card) ? [
                'title' => (string) ($card['title'] ?? ''),
                'text' => (string) ($card['text'] ?? ''),
            ] : null)
            ->filter()
            ->values()
            ->all();
    }

    private static function normalizeBullets(array $bullets): array
    {
        return collect($bullets)
            ->map(fn ($bullet) => trim((string) $bullet))
            ->filter()
            ->values()
            ->all();
    }
}
