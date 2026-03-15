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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Booking Widget', description: 'Public booking widget endpoints')]
class PublicBookingController extends Controller
{
    #[OA\Get(
        path: '/api/v1/public/widget/{businessToken}/config',
        tags: ['Booking Widget'],
        summary: 'Get widget config',
        parameters: [new OA\Parameter(name: 'businessToken', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Widget config')],
    )]
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

    #[OA\Get(
        path: '/api/v1/public/widget/{businessToken}/slots',
        tags: ['Booking Widget'],
        summary: 'Get available slots',
        parameters: [
            new OA\Parameter(name: 'businessToken', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Available slots')],
    )]
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

    #[OA\Post(
        path: '/api/v1/public/widget/{businessToken}/otp/send',
        tags: ['Booking Widget'],
        summary: 'Send OTP for widget booking',
        parameters: [new OA\Parameter(name: 'businessToken', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'OTP sent')],
    )]
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

    #[OA\Post(
        path: '/api/v1/public/widget/{businessToken}/otp/verify',
        tags: ['Booking Widget'],
        summary: 'Verify widget OTP',
        parameters: [new OA\Parameter(name: 'businessToken', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'OTP verified')],
    )]
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

    #[OA\Post(
        path: '/api/v1/public/widget/{businessToken}/reservations',
        tags: ['Booking Widget'],
        summary: 'Create reservation from widget',
        parameters: [new OA\Parameter(name: 'businessToken', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 201, description: 'Reservation created')],
    )]
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
