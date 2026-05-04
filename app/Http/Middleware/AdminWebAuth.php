<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminWebAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('admin.web.login');
        }

        if (Auth::guard('web')->user()->role !== 'admin') {
            Auth::guard('web')->logout();
            return redirect()->route('admin.web.login')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}

