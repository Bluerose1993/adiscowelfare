<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminModulePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = (string) $request->route()?->getName();
        if ($name === 'admin.import.index') {
            abort_unless($request->user()?->can('manage staff') || $request->user()?->can('manage dues'), 403, 'You do not have access to imports.');
            return $next($request);
        }
        $permission = match (true) {
            $name === 'admin.dashboard' => 'view dashboard',
            str_starts_with($name, 'admin.administrators.') => 'manage administrators',
            $name === 'admin.staff.import' || str_starts_with($name, 'admin.staff.') => 'manage staff',
            str_starts_with($name, 'admin.dues.'), str_starts_with($name, 'admin.import.') => 'manage dues',
            str_starts_with($name, 'admin.benefit-requests.') => 'review benefit requests',
            str_starts_with($name, 'admin.benefits.'), str_starts_with($name, 'admin.benefit-types.') => 'manage benefits',
            str_starts_with($name, 'admin.reports.'), str_starts_with($name, 'admin.exports.') => 'view reports',
            str_starts_with($name, 'admin.settings.') => 'manage settings',
            str_starts_with($name, 'admin.audit.') => 'view audit logs',
            default => null,
        };

        abort_if($permission && ! $request->user()?->can($permission), 403, 'You do not have access to this system option.');

        return $next($request);
    }
}
