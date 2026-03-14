<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\LeoMessageLog;
use App\Services\Leo\LeoBusinessResolver;
use App\Services\Leo\LeoChannelInterface;
use App\Services\Leo\LeoGeminiService;
use App\Services\Leo\LeoMultiBusinessSelectionService;
use App\Services\Leo\LeoSessionService;
use App\Services\Leo\LeoWhatsAppConversationTracker;
use App\Services\Leo\LeoWhatsAppCreditService;
use App\Services\Leo\TelegramChannel;
use App\Services\Leo\WhatsAppChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeoWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramChannel $telegramChannel,
        private readonly WhatsAppChannel $whatsappChannel,
        private readonly LeoBusinessResolver $resolver,
        private readonly LeoGeminiService $gemini,
        private readonly LeoMultiBusinessSelectionService $selectionService,
        private readonly LeoSessionService $sessionService,
        private readonly LeoWhatsAppConversationTracker $waTracker,
        private readonly LeoWhatsAppCreditService $waCredits,
    ) {}

    public function telegram(Request $request)
    {
        return $this->handleWebhook($request, $this->telegramChannel, 'telegram');
    }

    public function whatsapp(Request $request)
    {
        // GET challenge verification
        if ($request->isMethod('GET')) {
            if ($this->whatsappChannel->verifyWebhook($request)) {
                return response($request->query('hub_challenge'), 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $this->handleWebhook($request, $this->whatsappChannel, 'whatsapp');
    }

    private function handleWebhook(Request $request, LeoChannelInterface $channelImplementation, string $channelType)
    {
        try {
            if ($channelType === 'telegram' && ! $channelImplementation->verifyWebhook($request)) {
                return response()->json(['received' => true]);
            }

            $inbound = $channelImplementation->parseInbound($request);

            if (! $inbound) {
                return response()->json(['received' => true]);
            }

            $resolution = $this->resolver->resolve($channelType, $inbound->senderId);

            if ($resolution['status'] === 'none') {
                $errorMsg = $channelType === 'whatsapp'
                    ? 'Numéro non reconnu. Configurez d’abord votre WhatsApp dans le dashboard.'
                    : 'Canal non reconnu. Configurez d’abord votre Chat ID dans le dashboard.';

                $channelImplementation->sendMessage($inbound->senderId, $errorMsg);

                return response()->json(['received' => true]);
            }

            $channel = null;

            if ($resolution['status'] === 'multiple') {
                $pendingSelection = $this->sessionService->findPendingSelection($resolution['channels'], $inbound->senderId);

                if (! $pendingSelection) {
                    $anchorChannel = $resolution['channels']->first();

                    if ($anchorChannel) {
                        $this->sessionService->set(
                            $anchorChannel->id,
                            $inbound->senderId,
                            null,
                            pendingSelection: true,
                        );
                    }

                    $channelImplementation->sendMessage(
                        $inbound->senderId,
                        $this->selectionService->buildSelectionPrompt($resolution['channels']),
                    );

                    return response()->json(['received' => true]);
                }

                $selected = $this->selectionService->parseSelection($inbound->messageText, $resolution['channels']);

                if (! $selected) {
                    $channelImplementation->sendMessage(
                        $inbound->senderId,
                        $this->selectionService->buildSelectionPrompt($resolution['channels']),
                    );

                    return response()->json(['received' => true]);
                }

                $this->sessionService->clearPendingSelections($resolution['channels'], $inbound->senderId);
                $this->sessionService->set($selected->id, $inbound->senderId, $selected->business_id);
                $channel = $selected;
            } else {
                $channel = $resolution['channel'];
                $this->sessionService->set($channel->id, $inbound->senderId, $channel->business_id);
            }

            // WhatsApp Credit logic
            if ($channelType === 'whatsapp') {
                $hasWindow = $this->waTracker->hasActiveWindow($channel->id, $inbound->senderId, 'service');

                if (! $hasWindow) {
                    $cost = $this->waCredits->getConversationCost('service');
                    /** @var Business $business */
                    $business = $channel->business;
                    if (! $this->waCredits->hasSufficientCredit($business, $cost)) {
                        $channelImplementation->sendMessage(
                            $inbound->senderId,
                            'Votre crédit Léo WhatsApp est épuisé. Rechargez depuis votre tableau de bord.'
                        );

                        return response()->json(['received' => true]);
                    }

                    $this->waCredits->deduct($business, $cost);
                    $this->waTracker->openWindow($channel->id, $inbound->senderId, 'service', $cost);
                }
            }

            $responseText = $this->gemini->ask($channel->business_id, $channel->bot_name, $inbound->messageText);

            LeoMessageLog::query()->create([
                'channel_id' => $channel->id,
                'direction' => 'inbound',
                'sender_identifier' => $inbound->senderId,
                'raw_message' => $inbound->messageText,
                'created_at' => now(),
            ]);

            $channelImplementation->sendMessage($inbound->senderId, $responseText);

            LeoMessageLog::query()->create([
                'channel_id' => $channel->id,
                'direction' => 'outbound',
                'sender_identifier' => $inbound->senderId,
                'raw_message' => $responseText,
                'response_preview' => mb_substr($responseText, 0, 120),
                'created_at' => now(),
            ]);

        } catch (Throwable $exception) {
            Log::error("Leo $channelType webhook failed.", [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return response()->json(['received' => true]);
    }
}
