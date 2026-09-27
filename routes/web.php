<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserCorrectionRequestController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\AttendanceCorrection;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Illuminate\Http\Request;


Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('user.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create']);
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store']);
});


Route::middleware(['web', 'auth:web'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/attendance', [AttendanceController::class, 'create']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'userAttendanceIndex']);

    Route::get('/attendance/{id}', function ($id) {
        if (auth()->check() && auth()->user()->admin_status) {
            return redirect('/admin/attendance/' . $id);
        }
        return redirect('/attendance/detail/' . $id);
    });
    Route::get('/attendance/detail/{id}', [UserCorrectionRequestController::class, 'create']);
    Route::post('/attendance/{id}', function (Request $request, $id) {
        if (auth()->check() && auth()->user()->admin_status) {
            return app(AdminController::class)->update(
                app(UpdateAttendanceRequest::class),
                $id
            );
        }
        return app(UserCorrectionRequestController::class)->store($request, $id);
    });
    Route::get('/stamp_correction_request/list', [UserCorrectionRequestController::class, 'userApplicationIndex']);
    Route::get('/application/{id}', function ($id) {
        $correction = AttendanceCorrection::find($id);

        if (!$correction) {
            abort(404);
        }

        return redirect('/attendance/detail/' . $correction->attendance_id . '?status=' . $correction->approval_status->value);
    });
});

Route::middleware(['auth', 'auth:web', 'admin.check'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'destroy']);
    Route::get('/admin/attendance/list', [AdminController::class, 'attendanceIndex']);
    Route::get('/admin/attendance/{id}', [AdminController::class, 'show']);
    Route::post('/admin/attendance/{id}', [AdminController::class, 'update']);
    Route::get('/admin/staff/list', [AdminController::class, 'staffIndex']);
    Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'staffShow']);
});