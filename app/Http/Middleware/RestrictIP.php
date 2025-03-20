<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RestrictIp as RestrictedIP;
use Illuminate\Support\Facades\Auth;

class RestrictIP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // dd(auth()->user());
        if(Auth::check())
        {
            $restrictedIPs = RestrictedIP::where('created_by', \Auth::user()->creatorId())->pluck('ip')->toArray();
            // dd(in_array($request->ip(), $restrictedIPs),$restrictedIPs);
            if (in_array($request->ip(), $restrictedIPs) && Auth()->user()->type != 'super admin' && Auth()->user()->type != 'company') {
                // dd('hi');
                return response()->view('error.ip_restricted', [], 403);
            }
        }

        return $next($request);
    }
}
