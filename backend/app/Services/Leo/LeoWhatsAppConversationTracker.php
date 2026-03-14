<?php

namespace App\Services\Leo;

use App\Models\WhatsAppConversationWindow;

class LeoWhatsAppConversationTracker
{
    public function hasActiveWindow(string $channelId, string $contactPhone, string $type): bool
    {
        return WhatsAppConversationWindow::query()
            ->forContact($channelId, $contactPhone, $type)
            ->exists();
    }

    public function openWindow(string $channelId, string $contactPhone, string $type, int $costCents): WhatsAppConversationWindow
    {
        return WhatsAppConversationWindow::query()->create([
            'channel_id' => $channelId,
            'contact_phone' => $contactPhone,
            'conversation_type' => $type,
            'opened_at' => now(),
            'expires_at' => now()->addHours(24),
            'cost_cents' => $costCents,
            'created_at' => now(),
        ]);
    }

    public function purgeExpired(): int
    {
        return WhatsAppConversationWindow::query()
            ->where('expires_at', '<', now())
            ->delete();
    }
}
