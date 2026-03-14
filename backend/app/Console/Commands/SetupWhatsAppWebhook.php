<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupWhatsAppWebhook extends Command
{
    protected $signature = 'leo:setup-whatsapp-webhook';

    protected $description = 'Subscribes the WhatsApp phone number to the Meta app webhook.';

    public function handle(): int
    {
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $accessToken = (string) config('services.whatsapp.access_token');

        if ($phoneNumberId === '' || $accessToken === '') {
            $this->error('WhatsApp configuration (phone_number_id or access_token) is missing.');

            return Command::FAILURE;
        }

        $this->info("Subscribing phone number $phoneNumberId to WhatsApp Cloud API...");

        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/subscribed_apps");

        if ($response->successful()) {
            $this->info('Successfully subscribed to WhatsApp events.');
            $this->line($response->body());

            return Command::SUCCESS;
        }

        $this->error('Failed to subscribe.');
        $this->error($response->body());

        return Command::FAILURE;
    }
}
