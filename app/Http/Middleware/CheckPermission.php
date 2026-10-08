<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user account is suspended
        if (!$user->isActive()) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account has been suspended. Please contact system administrator.']);
        }

        // Administrator role inherently has all permissions
        if ($user->hasRole('admin', 'administrator')) {
            return $next($request);
        }

        // Check if user has required permission
        if (!$user->hasPermission($permission)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Access Denied: Your role ({$user->role}) does not have permission ({$permission}) to perform this action.",
                ], 403);
            }

            return redirect()->route('portal.dashboard')->with('error', "Access Denied: Your role ({$user->role}) does not have permission to access that section.");
        }

        return $next($request);
    }
}
