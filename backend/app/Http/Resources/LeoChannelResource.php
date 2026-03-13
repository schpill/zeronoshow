<?php

namespace App\Http\Resources;

use App\Models\LeoChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeoChannelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LeoChannel $channel */
        $channel = $this->resource;

        return [
            'id' => $channel->id,
            'business_id' => $channel->business_id,
            'channel' => $channel->channel,
            'bot_name' => $channel->bot_name,
            'is_active' => $channel->is_active,
            'external_identifier_masked' => $channel->maskedExternalIdentifier(),
            'created_at' => optional($channel->created_at)->toIso8601String(),
            'updated_at' => optional($channel->updated_at)->toIso8601String(),
        ];
    }
}
