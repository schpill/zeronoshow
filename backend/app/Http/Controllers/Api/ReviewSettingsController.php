<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSettingsRequest;
use App\Http\Resources\ReviewSettingsResource;
use App\Models\Business;
use Illuminate\Http\Request;

class ReviewSettingsController extends Controller
{
    public function show(Request $request): ReviewSettingsResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new ReviewSettingsResource($business);
    }

    public function update(ReviewSettingsRequest $request): ReviewSettingsResource
    {
        /** @var Business $business */
        $business = $request->user();

        $business->update($request->validated());

        return new ReviewSettingsResource($business->refresh());
    }
}
