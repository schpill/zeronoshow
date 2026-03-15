<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSettingsRequest;
use App\Http\Resources\ReviewSettingsResource;
use App\Models\Business;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reputation', description: 'Review settings endpoints')]
class ReviewSettingsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/review-settings',
        tags: ['Reputation'],
        summary: 'Get review settings',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Review settings')],
    )]
    public function show(Request $request): ReviewSettingsResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new ReviewSettingsResource($business);
    }

    #[OA\Patch(
        path: '/api/v1/review-settings',
        tags: ['Reputation'],
        summary: 'Update review settings',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Review settings updated')],
    )]
    public function update(ReviewSettingsRequest $request): ReviewSettingsResource
    {
        /** @var Business $business */
        $business = $request->user();

        $business->update($request->validated());

        return new ReviewSettingsResource($business->refresh());
    }
}
