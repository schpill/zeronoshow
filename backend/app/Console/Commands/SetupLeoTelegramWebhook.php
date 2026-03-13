<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupLeoTelegramWebhook extends Command
{
    protected $signature = 'leo:setup-telegram-webhook';

    protected $description = 'Register the Telegram webhook for Léo';

    public function handle(): int
    {
        $token = (string) config('services.telegram.token');
        $secret = (string) config('services.telegram.webhook_secret');
        $url = rtrim((string) config('app.url'), '/').'/api/v1/webhooks/leo/telegram';
        $baseUrl = "https://api.telegram.org/bot{$token}";

        Http::baseUrl($baseUrl)->post('/deleteWebhook');

        $response = Http::baseUrl($baseUrl)->post('/setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
        ]);

        if (! $response->successful()) {
            $this->error('Impossible de configurer le webhook Telegram.');

            return self::FAILURE;
        }

        $this->info("Webhook Telegram configuré sur {$url}");

        return self::SUCCESS;
    }
}
