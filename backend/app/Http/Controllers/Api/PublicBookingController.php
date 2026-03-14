<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidGuestTokenException;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpMaxAttemptsException;
use App\Exceptions\TooManyOtpRequestsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicStoreReservationRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\WidgetSetting;
use App\Services\BookingOtpService;
use App\Services\GuestToken;
use App\Services\SlotAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    public function config(Business $business): JsonResponse
    {
        /** @var WidgetSetting|null $setting */
        $setting = $business->widgetSetting;

        if ($setting === null || ! $setting->is_enabled) {
            return response()->json([
                'error' => ['message' => 'Le widget de réservation est actuellement désactivé.'],
            ], 423);
        }

        return response()->json([
            'config' => $setting->publicConfig(),
        ]);
    }

    public function slots(Request $request, Business $business, SlotAvailabilityService $service): JsonResponse
    {
        $date = $request->query('date');

        if ($date === null) {
            return response()->json([
                'error' => ['message' => 'Le paramètre date est obligatoire.'],
            ], 422);
        }

        $slots = $service->getAvailableSlots($business, $date);

        return response()->json(['slots' => $slots]);
    }

    public function sendOtp(
        SendOtpRequest $request,
        Business $business,
        BookingOtpService $otpService,
    ): JsonResponse {
        try {
            $otpService->send(
                $request->string('phone')->toString(),
                $request->ip(),
            );

            return response()->json(['message' => 'Code de vérification envoyé.']);
        } catch (TooManyOtpRequestsException $e) {
            return response()->json([
                'error' => ['message' => $e->getMessage()],
            ], 429);
        }
    }

    public function verifyOtp(
        VerifyOtpRequest $request,
        Business $business,
        BookingOtpService $otpService,
        GuestToken $guestToken,
    ): JsonResponse {
        $phone = $request->string('phone')->toString();
        $code = $request->string('code')->toString();

        try {
            $otpService->verify($phone, $code);
        } catch (OtpExpiredException $e) {
            return response()->json([
                'error' => ['message' => $e->getMessage()],
            ], 422);
        } catch (OtpInvalidException $e) {
            return response()->json([
                'error' => ['message' => $e->getMessage()],
            ], 422);
        } catch (OtpMaxAttemptsException $e) {
            return response()->json([
                'error' => ['message' => $e->getMessage()],
            ], 423);
        }

        $token = $guestToken->issue($phone, $business->id);

        return response()->json(['guest_token' => $token]);
    }

    public function store(
        PublicStoreReservationRequest $request,
        Business $business,
        GuestToken $guestToken,
    ): JsonResponse {
        try {
            $payload = $guestToken->verify($request->string('guest_token')->toString());
        } catch (InvalidGuestTokenException $e) {
            return response()->json([
                'error' => ['message' => $e->getMessage()],
            ], 422);
        }

        $scheduledAt = Carbon::parse($request->string('date')->toString().' '.$request->string('time')->toString());

        $customer = Customer::query()->firstOrCreate(
            ['phone' => $payload['phone']],
            [
                'reservations_count' => 0,
                'shows_count' => 0,
                'no_shows_count' => 0,
                'score_tier' => 'at_risk',
                'opted_out' => false,
                'is_blacklisted' => false,
                'is_vip' => false,
            ],
        );

        $customer->increment('reservations_count');

        $token = (string) Str::uuid();
        $expiresAt = $scheduledAt->copy()->subMinutes(15);

        $reservation = Reservation::query()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $request->string('guest_name')->toString(),
            'scheduled_at' => $scheduledAt,
            'guests' => $request->integer('party_size') ?: 1,
            'source' => 'widget',
            'status' => 'pending_verification',
            'phone_verified' => false,
            'confirmation_token' => $token,
            'token_expires_at' => $expiresAt,
            'status_changed_at' => now()->utc(),
        ]);

        return response()->json([
            'reservation' => ReservationResource::make($reservation->load('customer')),
        ], 201);
    }
}
