<?php

namespace App\Services\Leo;

use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Support\Collection;

class LeoMultiBusinessSelectionService
{
    /**
     * @param  Collection<int, LeoChannel>  $channels
     */
    public function buildSelectionPrompt(Collection $channels): string
    {
        $lines = $channels
            ->values()
            ->map(function (LeoChannel $channel, int $index): string {
                /** @var Business $business */
                $business = $channel->business;

                return sprintf('%d. %s', $index + 1, $business->name);
            })
            ->all();

        return "Pour quel établissement ?\n".implode("\n", $lines);
    }

    /**
     * @param  Collection<int, LeoChannel>  $channels
     */
    public function parseSelection(string $userMessage, Collection $channels): ?LeoChannel
    {
        $trimmed = trim($userMessage);

        if (is_numeric($trimmed)) {
            $index = (int) $trimmed - 1;

            return $channels->values()->get($index);
        }

        return $channels->first(function (LeoChannel $channel) use ($trimmed): bool {
            /** @var Business $business */
            $business = $channel->business;

            return str_contains(
                mb_strtolower($business->name),
                mb_strtolower($trimmed),
            );
        });
    }
}
