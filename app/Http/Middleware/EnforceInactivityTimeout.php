<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceInactivityTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutSeconds = max((int) Setting::value('session_timeout_minutes', 120), 1) * 60;
        $lastActivity = (int) $request->session()->get('last_activity_at', now()->timestamp);

        if (now()->timestamp - $lastActivity >= $timeoutSeconds) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired due to inactivity.'], 401);
            }

            return redirect()->route('login')->with('status', 'Your session expired due to inactivity. Please sign in again.');
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
