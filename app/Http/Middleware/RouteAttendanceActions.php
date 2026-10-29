<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateAttendanceRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
