<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\LeoMessageLog;
use App\Services\Leo\LeoBusinessResolver;
use App\Services\Leo\LeoGeminiService;
use App\Services\Leo\LeoMultiBusinessSelectionService;
use App\Services\Leo\LeoSessionService;
use App\Services\Leo\TelegramChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeoWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramChannel $telegramChannel,
        private readonly LeoBusinessResolver $resolver,
        private readonly LeoGeminiService $gemini,
        private readonly LeoMultiBusinessSelectionService $selectionService,
        private readonly LeoSessionService $sessionService,
    ) {}

    public function telegram(Request $request)
    {
        try {
            if (! $this->telegramChannel->verifyWebhook($request)) {
                return response()->json(['received' => true]);
            }

            $inbound = $this->telegramChannel->parseInbound($request);

            if (! $inbound) {
                return response()->json(['received' => true]);
            }

            $resolution = $this->resolver->resolve('telegram', $inbound->senderId);

            if ($resolution['status'] === 'none') {
                $this->telegramChannel->sendMessage($inbound->senderId, 'Canal non reconnu. Configurez d’abord votre Chat ID dans le dashboard.');

                return response()->json(['received' => true]);
            }

            if ($resolution['status'] === 'multiple') {
                $selected = $this->selectionService->parseSelection($inbound->messageText, $resolution['channels']);

                if (! $selected) {
                    $this->telegramChannel->sendMessage(
                        $inbound->senderId,
                        $this->selectionService->buildSelectionPrompt($resolution['channels']),
                    );

                    return response()->json(['received' => true]);
                }

                $this->sessionService->set($selected->id, $inbound->senderId, $selected->business_id);
                $channel = $selected;
            } else {
                $channel = $resolution['channel'];
                $this->sessionService->set($channel->id, $inbound->senderId, $channel->business_id);
            }

            $responseText = $this->gemini->ask($channel->business_id, $channel->bot_name, $inbound->messageText);

            LeoMessageLog::query()->create([
                'channel_id' => $channel->id,
                'direction' => 'inbound',
                'sender_identifier' => $inbound->senderId,
                'raw_message' => $inbound->messageText,
                'created_at' => now(),
            ]);

            $this->telegramChannel->sendMessage($inbound->senderId, $responseText);

            LeoMessageLog::query()->create([
                'channel_id' => $channel->id,
                'direction' => 'outbound',
                'sender_identifier' => $inbound->senderId,
                'raw_message' => $responseText,
                'response_preview' => mb_substr($responseText, 0, 120),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Leo telegram webhook failed.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json(['received' => true]);
    }
}
