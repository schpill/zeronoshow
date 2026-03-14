<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerCrmRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;

class CustomerCrmController extends Controller
{
    public function update(UpdateCustomerCrmRequest $request, Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        $customer->update($request->validated());

        return new CustomerResource($customer->refresh());
    }
}
