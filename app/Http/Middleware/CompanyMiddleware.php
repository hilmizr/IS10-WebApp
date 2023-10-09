<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // check guard
        // dd(Auth::guard()->name());
        if (Auth::guard('company_user')->check()) {
            Auth::login(Auth::guard('company_user')->user());
            return $next($request);
        }
        // dd('here');

        return redirect()->route('company-login'); // Redirect to the company login page
    }
}
