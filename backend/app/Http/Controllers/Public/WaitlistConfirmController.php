<?php

namespace App\Http\Controllers\Public;

use App\Enums\WaitlistStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\WaitlistEntry;
use App\Services\WaitlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaitlistConfirmController extends Controller
{
    public function confirm(Request $request, string $token, WaitlistService $waitlistService): RedirectResponse
    {
        $entry = WaitlistEntry::query()
            ->where('confirmation_token', $token)
            ->where('status', WaitlistStatusEnum::Notified)
            ->where('expires_at', '>', now())
            ->first();

        if (! $entry) {
            return redirect(config('app.frontend_url').'/waitlist/expired');
        }

        $waitlistService->confirmSlot($entry);

        return redirect(config('app.frontend_url').'/waitlist/confirmed?name='.urlencode($entry->client_name).'&slot='.urlencode($entry->slot_date->format('Y-m-d').'T'.$entry->slot_time));
    }

    public function decline(Request $request, string $token, WaitlistService $waitlistService): RedirectResponse
    {
        $entry = WaitlistEntry::query()
            ->where('confirmation_token', $token)
            ->first();

        if (! $entry) {
            return redirect(config('app.frontend_url').'/waitlist/expired');
        }

        if ($entry->status === WaitlistStatusEnum::Notified) {
            $waitlistService->declineSlot($entry);
        }

        return redirect(config('app.frontend_url').'/waitlist/declined');
    }
}
