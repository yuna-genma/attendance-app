<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminCheck
{

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->admin_status) {
            return $next($request);
        }
        return redirect('/admin/login')->with('error', '管理者権限がありません');
    }
}
