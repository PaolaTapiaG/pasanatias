<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Models\SystemSetting;
use App\Support\PortalContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        $settings = $this->settingsBundle();

        return view('configuracion.index', [
            'system' => $settings['general'],
            'messaging' => $settings['messaging'],
            'mail' => $settings['mail'],
            'portal' => $settings['portal'],
            'adminProfile' => Auth::user(),
            'messageStats' => $this->messageStats(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $this->settingsBundle();
        $this->messageStats();
    }

    private function settingsBundle(): array
    {
        return Cache::remember('configuracion:settings-bundle', now()->addDays(7), function () {
            $defaults = [
                'general' => $this->defaultGeneralSettings(),
                'messaging' => $this->defaultMessagingSettings(),
                'mail' => $this->defaultMailSettings(),
                'portal' => PortalContent::defaults(),
            ];
            $rows = SystemSetting::query()
                ->whereIn('key', array_keys($defaults))
                ->get(['key', 'value'])
                ->keyBy('key');

            foreach ($defaults as $key => $value) {
                $stored = $rows->get($key)?->value;
                $defaults[$key] = $key === 'portal'
                    ? PortalContent::merge(is_array($stored) ? $stored : [])
                    : (is_array($stored) ? array_replace($value, $stored) : $value);
            }

            return $defaults;
        });
    }

    private function messageStats(): array
    {
        return Cache::remember('configuracion:message-stats', now()->addDays(7), function () {
            $summary = SmsMessage::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE status IN ('sent', 'queued', 'accepted', 'delivered', 'logged')) as sent,
                    COUNT(*) FILTER (WHERE status = 'failed') as failed,
                    MAX(created_at) as last_message_at
                ")
                ->first();

            return [
                'total' => (int) ($summary?->total ?? 0),
                'sent' => (int) ($summary?->sent ?? 0),
                'failed' => (int) ($summary?->failed ?? 0),
                'last_message_at' => $summary?->last_message_at,
            ];
        });
    }

    public function update(Request $request): RedirectResponse
    {
        Log::info('[SYSTEM SETTINGS UPDATE] Incoming request', [
            'has_company_logo' => $request->hasFile('company_logo'),
            'content_type' => $request->header('Content-Type'),
        ]);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_alias' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'date_format' => ['nullable', 'string', 'max:30'],
            'currency' => ['nullable', 'string', 'max:10'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_nit' => ['nullable', 'string', 'max:40'],
            'company_logo' => ['nullable', 'image', 'max:2048'],
            'company_description' => ['nullable', 'string', 'max:500'],
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_label' => ['nullable', 'string', 'max:120'],
            'map_icon' => ['nullable', 'string', 'max:40'],
            'theme_preference' => ['required', 'string', 'in:light,dark'],
            'portal_home_kicker' => ['nullable', 'string', 'max:80'],
            'portal_home_title' => ['nullable', 'string', 'max:180'],
            'portal_home_intro' => ['nullable', 'string', 'max:700'],
            'portal_home_video_url' => ['nullable', 'string', 'max:500'],
            'portal_home_video_file' => ['nullable', 'file', 'mimes:mp4,webm,ogg,mov', 'max:30720'],
            'portal_home_image_1' => ['nullable', 'image', 'max:4096'],
            'portal_home_image_2' => ['nullable', 'image', 'max:4096'],
            'portal_home_image_3' => ['nullable', 'image', 'max:4096'],
            'portal_featured_title' => ['nullable', 'string', 'max:180'],
            'portal_featured_text' => ['nullable', 'string', 'max:700'],
            'portal_schedule_summary' => ['nullable', 'string', 'max:500'],
            'portal_payment_note' => ['nullable', 'string', 'max:500'],
            'portal_pages' => ['nullable', 'array'],
            'portal_pages.*.kicker' => ['nullable', 'string', 'max:80'],
            'portal_pages.*.title' => ['nullable', 'string', 'max:180'],
            'portal_pages.*.intro' => ['nullable', 'string', 'max:800'],
            'portal_pages.*.hero' => ['nullable', 'string', 'max:120'],
            'portal_pages.*.cards_text' => ['nullable', 'string', 'max:2500'],
            'portal_pages.*.bullets_text' => ['nullable', 'string', 'max:1500'],
            'multa_reconexion' => ['nullable', 'numeric', 'min:0'],
            'multa_mora' => ['nullable', 'numeric', 'min:0'],
            'multa_retraso' => ['nullable', 'numeric', 'min:0'],
            'included_m3' => ['nullable', 'numeric', 'min:0'],
            'fixed_charge' => ['nullable', 'numeric', 'min:0'],
            'excess_rate' => ['nullable', 'numeric', 'min:0'],
            'cutoff_threshold_m3' => ['nullable', 'numeric', 'min:0'],
            'reconnection_fee' => ['nullable', 'numeric', 'min:0'],
            'sewer_fixed_charge' => ['nullable', 'numeric', 'min:0'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'sms_driver' => ['nullable', 'string', Rule::in(['log', 'android_gateway', 'twilio'])],
            'sms_gateway_provider' => ['nullable', 'string', Rule::in(['smsgate', 'generic'])],
            'sms_country_code' => ['nullable', 'string', 'max:8'],
            'sms_sender_name' => ['nullable', 'string', 'max:80'],
            'sms_gateway_url' => ['nullable', 'string', 'max:255'],
            'sms_gateway_username' => ['nullable', 'string', 'max:150'],
            'sms_gateway_password' => ['nullable', 'string', 'max:255'],
            'sms_gateway_api_key' => ['nullable', 'string', 'max:500'],
            'sms_gateway_device_id' => ['nullable', 'string', 'max:120'],
            'sms_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'mail_mailer' => ['nullable', 'string', Rule::in(['log', 'smtp', 'brevo_api'])],
            'mail_api_key' => ['nullable', 'string', 'max:500'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'string', Rule::in(['tls', 'ssl', 'null'])],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:150'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
        ]);

        $general = SystemSetting::getValue('general', $this->defaultGeneralSettings());
        $messaging = SystemSetting::getValue('messaging', $this->defaultMessagingSettings());
        $mail = SystemSetting::getValue('mail', $this->defaultMailSettings());
        $portal = PortalContent::merge(SystemSetting::getValue('portal', []));
        $logoPath = $general['company_logo'] ?? null;

        if ($request->hasFile('company_logo') && $request->file('company_logo')->isValid()) {
            Log::info('[SYSTEM SETTINGS UPDATE] Company logo detected', [
                'original_name' => $request->file('company_logo')->getClientOriginalName(),
                'mime_type' => $request->file('company_logo')->getMimeType(),
                'size' => $request->file('company_logo')->getSize(),
            ]);

            if ($logoPath && Str::startsWith($logoPath, 'storage/')) {
                Storage::disk('public')->delete(Str::after($logoPath, 'storage/'));
            }

            $filename = 'empresa_logo_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . strtolower($request->file('company_logo')->extension() ?: 'png');
            $stored = $request->file('company_logo')->storeAs('empresa', $filename, 'public');
            $logoPath = 'storage/' . $stored;

            Log::info('[SYSTEM SETTINGS UPDATE] Company logo stored', [
                'stored_relative_path' => $stored,
                'public_path' => $logoPath,
                'exists_on_disk' => $stored ? Storage::disk('public')->exists($stored) : false,
            ]);
        } elseif ($request->hasFile('company_logo')) {
            Log::warning('[SYSTEM SETTINGS UPDATE] Company logo file arrived but is invalid');
        }

        SystemSetting::putValue('general', [
            'company_name' => $data['company_name'],
            'company_alias' => $data['company_alias'] ?? null,
            'support_email' => $data['support_email'] ?? null,
            'support_phone' => $data['support_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'timezone' => $data['timezone'] ?? ($general['timezone'] ?? config('app.timezone', 'America/La_Paz')),
            'date_format' => $data['date_format'] ?? ($general['date_format'] ?? 'd/m/Y'),
            'currency' => $data['currency'] ?? ($general['currency'] ?? 'Bs'),
            'company_email' => $data['company_email'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'company_nit' => $data['company_nit'] ?? ($general['company_nit'] ?? null),
            'company_logo' => $logoPath,
            'company_description' => $data['company_description'] ?? null,
            'gps_latitude' => isset($data['gps_latitude']) ? (float) $data['gps_latitude'] : null,
            'gps_longitude' => isset($data['gps_longitude']) ? (float) $data['gps_longitude'] : null,
            'map_label' => $data['map_label'] ?? null,
            'map_icon' => $data['map_icon'] ?? 'water',
            'theme_preference' => $data['theme_preference'],
            'multa_reconexion' => (float) ($general['multa_reconexion'] ?? 0),
            'multa_mora' => (float) ($general['multa_mora'] ?? 0),
            'multa_retraso' => (float) ($general['multa_retraso'] ?? 0),
            'included_m3' => (float) ($general['included_m3'] ?? 10),
            'fixed_charge' => (float) ($general['fixed_charge'] ?? 20),
            'excess_rate' => (float) ($general['excess_rate'] ?? 3),
            'cutoff_threshold_m3' => (float) ($general['cutoff_threshold_m3'] ?? 30),
            'reconnection_fee' => (float) ($general['reconnection_fee'] ?? 30),
            'sewer_fixed_charge' => (float) ($general['sewer_fixed_charge'] ?? 0),
            'maintenance_mode' => (bool) ($general['maintenance_mode'] ?? false),
        ]);

        $portal['home_kicker'] = $data['portal_home_kicker'] ?? $portal['home_kicker'];
        $portal['home_title'] = $data['portal_home_title'] ?? $portal['home_title'];
        $portal['home_intro'] = $data['portal_home_intro'] ?? $portal['home_intro'];
        $portal['home_video_url'] = $data['portal_home_video_url'] ?? $portal['home_video_url'];
        $portal['featured_title'] = $data['portal_featured_title'] ?? $portal['featured_title'];
        $portal['featured_text'] = $data['portal_featured_text'] ?? $portal['featured_text'];
        $portal['schedule_summary'] = $data['portal_schedule_summary'] ?? $portal['schedule_summary'];
        $portal['payment_note'] = $data['portal_payment_note'] ?? $portal['payment_note'];
        $portal['home_video_path'] = $this->storePortalFile($request, 'portal_home_video_file', $portal['home_video_path'] ?? null, 'video');

        foreach ([1, 2, 3] as $index) {
            $key = 'home_image_' . $index;
            $portal[$key] = $this->storePortalFile($request, 'portal_' . $key, $portal[$key] ?? null, 'image');
        }

        foreach (($data['portal_pages'] ?? []) as $key => $pageData) {
            if (!isset($portal['pages'][$key])) {
                continue;
            }

            $portal['pages'][$key]['kicker'] = $pageData['kicker'] ?? $portal['pages'][$key]['kicker'];
            $portal['pages'][$key]['title'] = $pageData['title'] ?? $portal['pages'][$key]['title'];
            $portal['pages'][$key]['intro'] = $pageData['intro'] ?? $portal['pages'][$key]['intro'];
            $portal['pages'][$key]['hero'] = $pageData['hero'] ?? $portal['pages'][$key]['hero'];
            $portal['pages'][$key]['cards'] = PortalContent::textToCards(
                $pageData['cards_text'] ?? null,
                $portal['pages'][$key]['cards'] ?? []
            );
            $portal['pages'][$key]['bullets'] = PortalContent::textToBullets(
                $pageData['bullets_text'] ?? null,
                $portal['pages'][$key]['bullets'] ?? []
            );
        }

        SystemSetting::putValue('portal', $portal);

        if ($request->hasAny(['sms_driver', 'sms_gateway_provider', 'sms_country_code', 'sms_sender_name', 'sms_gateway_url', 'sms_enabled', 'email_enabled'])) {
            SystemSetting::putValue('messaging', [
                'sms_driver' => $data['sms_driver'] ?? ($messaging['sms_driver'] ?? 'log'),
                'sms_country_code' => $data['sms_country_code'] ?? ($messaging['sms_country_code'] ?? '+591'),
                'sms_sender_name' => $data['sms_sender_name'] ?? ($messaging['sms_sender_name'] ?? 'EPSAS'),
                'sms_notifications_phone' => $messaging['sms_notifications_phone'] ?? null,
                'sms_gateway_provider' => $data['sms_gateway_provider'] ?? ($messaging['sms_gateway_provider'] ?? 'smsgate'),
                'sms_gateway_url' => $data['sms_gateway_url'] ?? null,
                'sms_gateway_api_key' => $data['sms_gateway_api_key'] ?? null,
                'sms_gateway_username' => $data['sms_gateway_username'] ?? null,
                'sms_gateway_password' => $data['sms_gateway_password'] ?? null,
                'sms_gateway_device_id' => $data['sms_gateway_device_id'] ?? null,
                'sms_gateway_header' => $messaging['sms_gateway_header'] ?? 'X-API-Key',
                'sms_enabled' => (bool) ($data['sms_enabled'] ?? false),
                'email_enabled' => (bool) ($data['email_enabled'] ?? false),
            ]);
        }

        $mailEncryption = $data['mail_encryption'] ?? ($mail['encryption'] ?? 'tls');
        $mailEncryption = $mailEncryption === 'null' ? null : $mailEncryption;

        if ($request->hasAny(['mail_mailer', 'mail_api_key', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name'])) {
            SystemSetting::putValue('mail', [
                'mailer' => $data['mail_mailer'] ?? ($mail['mailer'] ?? 'log'),
                'host' => $data['mail_host'] ?? ($mail['host'] ?? null),
                'port' => (int) ($data['mail_port'] ?? ($mail['port'] ?? 587)),
                'encryption' => $mailEncryption,
                'username' => $data['mail_username'] ?? null,
                'password' => $data['mail_password'] ?? null,
                'api_key' => $data['mail_api_key'] ?? null,
                'from_address' => $data['mail_from_address'] ?? ($mail['from_address'] ?? null),
                'from_name' => $data['mail_from_name'] ?? ($mail['from_name'] ?? 'EPSAS'),
            ]);
        }

        Cache::forget('shared_company_settings');
        Cache::forget('configuracion:message-stats');
        Cache::forget('configuracion:settings-bundle');
        Cache::forget('system_setting:general');
        Cache::forget('system_setting:messaging');
        Cache::forget('system_setting:mail');
        Cache::forget('system_setting:portal');

        return redirect()
            ->route('admin.configuracion.index')
            ->with('success', 'La configuracion del sistema se actualizo correctamente.');
    }

    private function storePortalFile(Request $request, string $field, ?string $currentPath, string $type): ?string
    {
        if (!$request->hasFile($field) || !$request->file($field)->isValid()) {
            return $currentPath;
        }

        if ($currentPath && Str::startsWith($currentPath, 'storage/')) {
            Storage::disk('public')->delete(Str::after($currentPath, 'storage/'));
        }

        $file = $request->file($field);
        $extension = strtolower($file->extension() ?: ($type === 'video' ? 'mp4' : 'jpg'));
        $filename = 'portal_' . $type . '_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;
        $stored = $file->storeAs('portal', $filename, 'public');

        return 'storage/' . $stored;
    }

    private function defaultGeneralSettings(): array
    {
        return [
            'company_name' => 'EPSAS',
            'company_alias' => 'Panel administrativo',
            'support_email' => null,
            'support_phone' => null,
            'address' => null,
            'timezone' => config('app.timezone', 'America/La_Paz'),
            'date_format' => 'd/m/Y',
            'currency' => 'Bs',
            'company_email' => null,
            'company_phone' => null,
            'company_nit' => null,
            'company_logo' => null,
            'company_description' => null,
            'gps_latitude' => -16.500000,
            'gps_longitude' => -68.150000,
            'map_label' => 'Oficina central EPSAS',
            'map_icon' => 'water',
            'theme_preference' => 'light',
            'multa_reconexion' => 0,
            'multa_mora' => 0,
            'multa_retraso' => 0,
            'included_m3' => 10,
            'fixed_charge' => 20,
            'excess_rate' => 3,
            'cutoff_threshold_m3' => 30,
            'reconnection_fee' => 30,
            'sewer_fixed_charge' => 0,
            'maintenance_mode' => false,
        ];
    }

    private function defaultMessagingSettings(): array
    {
        return [
            'sms_driver' => config('sms.default', 'log'),
            'sms_country_code' => config('sms.default_country_code', '+591'),
            'sms_sender_name' => 'EPSAS',
            'sms_notifications_phone' => null,
            'sms_gateway_provider' => 'smsgate',
            'sms_gateway_url' => null,
            'sms_gateway_api_key' => null,
            'sms_gateway_username' => null,
            'sms_gateway_password' => null,
            'sms_gateway_device_id' => null,
            'sms_gateway_header' => 'X-API-Key',
            'sms_enabled' => true,
            'email_enabled' => true,
        ];
    }

    private function defaultMailSettings(): array
    {
        return [
            'mailer' => config('mail.default', 'log'),
            'host' => env('MAIL_HOST', 'smtp.example.com'),
            'port' => (int) env('MAIL_PORT', 587),
            'encryption' => env('MAIL_MAILER') === 'smtp' ? (env('MAIL_SCHEME') ?: 'tls') : 'tls',
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'api_key' => null,
            'from_address' => env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME', 'EPSAS'),
        ];
    }
}
