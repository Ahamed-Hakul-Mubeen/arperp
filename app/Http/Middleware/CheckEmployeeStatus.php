<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeeStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        if (Auth::check()) {
            $user = Auth::user();

            // Check if the employee's login is disabled
            if ($user->is_enable_login == 0) {

                // Log out the user
                Auth::logout();

                // Invalidate the session after flashing the error message
                $request->session()->invalidate();

                // Regenerate session token
                $request->session()->regenerateToken();

                // Redirect to login page
                return redirect()->route('login')
    ->with('error', 'Your account has been disabled by the company.');
            }
        }
        return $next($request);
    }
}
