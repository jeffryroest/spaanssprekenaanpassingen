<?php

namespace App\Http\Middleware;

use App\Access\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEntitled
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next, string $entitlement): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        if ($this->entitlements->snapshotFor($user)->allows($entitlement)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'schema_version' => '1.0.0',
                'error' => [
                    'code' => 'entitlement_required',
                    'message' => 'Je account heeft geen actieve toegang tot dit onderdeel.',
                ],
            ], 403);
        }

        return redirect()
            ->route('trial-week.show')
            ->with('access_notice', 'Dit onderdeel valt buiten je huidige toegang.');
    }
}
