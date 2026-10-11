<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateAttendanceRequest;

class RouteAttendanceActions
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->admin_status && ($request->is('admin*')) || str_contains($request->header('referer'), '/admin/')) {
            return app(AdminController::class)->update(
                app(UpdateAttendanceRequest::class),
                $request->route('id')
            );
        }
        return $next($request);
    }
}
