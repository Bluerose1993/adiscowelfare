<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('staff.password.*', 'logout')) {
            return redirect()->route('staff.password.edit')
                ->with('status', 'You must change your temporary password before using the portal.');
        }

        return $next($request);
    }
}
