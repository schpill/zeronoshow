<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class AllowTelegramWebhookIps
{
    /**
     * @var list<string>
     */
    private const ALLOWED_RANGES = [
        '149.154.160.0/20',
        '91.108.4.0/22',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! IpUtils::checkIp((string) $request->ip(), self::ALLOWED_RANGES)) {
            abort(403);
        }

        return $next($request);
    }
}
