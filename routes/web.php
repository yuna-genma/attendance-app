<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCorrectionRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserCorrectionRequestController;
use App\Http\Controllers\AdminAuthController;
use App\Models\AttendanceCorrection;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('user.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store']);
});


Route::middleware(['web', 'auth:web'])->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/attendance');
    })->middleware(['signed',])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');

    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::middleware(['verified'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'create']);
        Route::post('/attendance', [AttendanceController::class, 'store']);
        Route::get('/attendance/list', [AttendanceController::class, 'userAttendanceIndex']);
        Route::get('/attendance/report', [ReportController::class, 'index']);

        Route::get('/attendance/{id}', function (Request $request, $id) {
            if ($request->is('admin*') || str_contains($request->header('referer'), '/admin/')) {
                return redirect('/admin/attendance/' . $id);
            }
            return redirect('/attendance/detail/' . $id);
        });
        Route::get('/attendance/detail/{id}', [UserCorrectionRequestController::class, 'create']);
        Route::post('/attendance/{id}', [UserCorrectionRequestController::class, 'store'])
            ->middleware('route.attendance');
        Route::get('/stamp_correction_request/list', function (Request $request) {
            if (auth()->check() && auth()->user()->admin_status && str_contains($request->header('referer'), '/admin/')) {
                return app(AdminCorrectionRequestController::class)->adminApplicationIndex();
            }
            return app(UserCorrectionRequestController::class)->userApplicationIndex();
        });

        Route::get('/application/{id}', function (Request $request, $id) {
            $correction = AttendanceCorrection::find($id);

            if (!$correction) {
                abort(404);
            }

            return redirect('/attendance/detail/' . $correction->attendance_id . '?status=' . $correction->approval_status);
        });

    });
});

Route::middleware(['auth', 'auth:web', 'admin.check'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'destroy']);
    Route::get('/admin/attendance/list', [AdminController::class, 'attendanceIndex']);
    Route::get('/admin/attendance/{id}', [AdminController::class, 'show']);
    Route::put('/admin/attendance/{id}', [AdminController::class, 'update']);
    Route::get('/admin/staff/list', [AdminController::class, 'staffIndex']);
    Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'staffShow']);
    Route::post('/export', [AdminController::class, 'exportCsv']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'showApplication']);
    Route::put('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionRequestController::class, 'approve']);
});