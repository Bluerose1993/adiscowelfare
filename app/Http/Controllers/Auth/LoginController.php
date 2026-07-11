<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $key = Str::lower($request->ip().'|'.$request->input('login'));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Too many login attempts. Please try again shortly.',
            ]);
        }

        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $login, 'password' => $request->input('password'), 'status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => 'The login details are invalid or the account is inactive.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $audit->log('user_login', $request->user(), [], ['username' => $request->user()->username], $request);

        if ($request->user()->hasRole('Administrator')) {
            return redirect()->intended($this->adminLanding($request->user()));
        }

        if ($request->user()->must_change_password) {
            return redirect()->route('staff.password.edit');
        }

        return redirect()->intended(route('staff.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    private function adminLanding($user): string
    {
        $routes = [
            'view dashboard' => 'admin.dashboard',
            'manage staff' => 'admin.staff.index',
            'manage dues' => 'admin.dues.record',
            'manage benefits' => 'admin.benefits.index',
            'review benefit requests' => 'admin.benefit-requests.index',
            'view reports' => 'admin.reports.dues',
            'manage administrators' => 'admin.administrators.index',
            'manage settings' => 'admin.settings.index',
            'view audit logs' => 'admin.audit.index',
        ];
        foreach ($routes as $permission => $route) {
            if ($user->can($permission)) {
                return route($route);
            }
        }

        return route('login');
    }
}
