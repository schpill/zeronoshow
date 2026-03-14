<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\Customer;

class CustomerPolicy
{
    public function view(Business $business, Customer $customer): bool
    {
        return $customer->reservations()
            ->where('business_id', $business->id)
            ->exists();
    }
}
