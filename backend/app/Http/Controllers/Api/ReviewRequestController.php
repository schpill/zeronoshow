<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewRequestResource;
use App\Models\ReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ReviewRequest::query()
            ->with('reservation')
            ->where('business_id', $request->user()->id)
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->string('platform')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }

        return ReviewRequestResource::collection($query->paginate(20));
    }

    public function stats(Request $request): JsonResponse
    {
        $requests = ReviewRequest::query()
            ->where('business_id', $request->user()->id)
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->subDays(30))
            ->get();

        $totalSent = $requests->count();
        $totalClicked = $requests->where('status', 'clicked')->count();

        return response()->json([
            'total_sent' => $totalSent,
            'total_clicked' => $totalClicked,
            'click_rate_percent' => $totalSent === 0 ? 0.0 : round(($totalClicked / $totalSent) * 100, 2),
        ]);
    }
}
