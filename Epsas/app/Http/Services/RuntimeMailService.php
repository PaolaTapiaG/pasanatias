<?php

namespace App\Http\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class RuntimeMailService
{
    public function __construct(private BrevoMailService $brevoMailService)
    {
    }

    public function send(string $recipient, object $mailable): void
    {
        $settings = $this->resolveMailSettings(SystemSetting::getValue('mail', []));
        $mailer = $settings['mailer'] ?? config('mail.default', 'log');

        if ($mailer === 'log') {
            throw new \RuntimeException('El correo real no esta configurado. Cambia el canal de email de "Solo log" a SMTP real o configura MAIL_MAILER=smtp en el archivo .env.');
        }

        if ($mailer === 'brevo_api') {
            $this->brevoMailService->send($recipient, $mailable, $settings);
            return;
        }

        if ($mailer === 'smtp' && !empty($settings['host'])) {
            $this->applySmtpConfig($settings);
        }

        Mail::to($recipient)->send($mailable);
    }

    private function resolveMailSettings(mixed $storedSettings): array
    {
        $stored = is_array($storedSettings) ? $storedSettings : [];
        $env = $this->envMailSettings();
        $storedMailer = $stored['mailer'] ?? null;

        if (($storedMailer === null || $storedMailer === 'log') && $this->hasRealEnvMailer($env)) {
            return $env;
        }

        $cleanStored = array_filter(
            $stored,
            fn ($value) => !($value === null || $value === '')
        );

        return array_replace($env, $cleanStored);
    }

    private function envMailSettings(): array
    {
        return [
            'mailer' => config('mail.default', 'log'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => (int) config('mail.mailers.smtp.port', 587),
            'encryption' => config('mail.mailers.smtp.scheme') ?? config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'api_key' => null,
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
    }

    private function hasRealEnvMailer(array $settings): bool
    {
        $mailer = $settings['mailer'] ?? 'log';

        if ($mailer === 'smtp') {
            return !empty($settings['host'])
                && !empty($settings['from_address'])
                && $settings['host'] !== '127.0.0.1';
        }

        return !in_array($mailer, ['log', 'array'], true);
    }

    private function applySmtpConfig(array $settings): void
    {
        $scheme = $settings['encryption'] ?? null;
        $scheme = in_array($scheme, ['', 'null', 'none'], true) ? null : $scheme;

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings['host'] ?? '127.0.0.1');
        Config::set('mail.mailers.smtp.port', (int) ($settings['port'] ?? 587));
        Config::set('mail.mailers.smtp.username', $settings['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $settings['password'] ?? null);
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.encryption', $scheme);
        Config::set('mail.from.address', $settings['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $settings['from_name'] ?? config('mail.from.name'));
    }
}
