<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWidgetSettingRequest;
use App\Http\Resources\WidgetSettingResource;
use App\Models\BookingOtp;
use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Http\JsonResponse;

class WidgetSettingController extends Controller
{
    public function show(Business $business): JsonResponse
    {
        $setting = $business->widgetSetting ?? WidgetSetting::query()->create([
            'business_id' => $business->id,
        ]);

        return response()->json([
            'setting' => WidgetSettingResource::make($setting->load('business')),
        ]);
    }

    public function update(UpdateWidgetSettingRequest $request, Business $business): JsonResponse
    {
        $setting = $business->widgetSetting ?? WidgetSetting::query()->create([
            'business_id' => $business->id,
        ]);

        $setting->update($request->validated());

        return response()->json([
            'setting' => WidgetSettingResource::make($setting->refresh()->load('business')),
        ]);
    }

    public function stats(Business $business): JsonResponse
    {
        $totalWidget = $business->reservations()
            ->where('source', 'widget')
            ->count();

        $thisMonthWidget = $business->reservations()
            ->where('source', 'widget')
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();

        $otpsSent = BookingOtp::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $conversionRate = $otpsSent > 0
            ? round(($totalWidget / $otpsSent) * 100, 1)
            : 0.0;

        return response()->json([
            'widget_reservations_count' => $totalWidget,
            'widget_reservations_this_month' => $thisMonthWidget,
            'widget_conversion_rate' => $conversionRate,
        ]);
    }
}
