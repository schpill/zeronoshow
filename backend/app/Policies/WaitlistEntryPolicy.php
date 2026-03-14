<?php

namespace App\Policies;

use App\Enums\WaitlistStatusEnum;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class WaitlistEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(Business $business): bool
    {
        return true;
    }

    public function view(Business $business, WaitlistEntry $entry): bool
    {
        return $business->id === $entry->business_id;
    }

    public function create(Business $business): bool
    {
        return true;
    }

    public function update(Business $business, WaitlistEntry $entry): bool
    {
        return $business->id === $entry->business_id;
    }

    public function delete(Business $business, WaitlistEntry $entry): bool
    {
        return $business->id === $entry->business_id;
    }

    public function notify(Business $business, WaitlistEntry $entry): bool
    {
        return $business->id === $entry->business_id && $entry->status === WaitlistStatusEnum::Pending;
    }
}
