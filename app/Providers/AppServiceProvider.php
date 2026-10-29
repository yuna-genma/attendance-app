<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Policies\AttendanceRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Attendance::class, AttendanceRecordPolicy::class);
        Gate::define('update', [AttendanceRecordPolicy::class, 'updateCorrection']);
        Gate::define('delete', [AttendanceRecordPolicy::class, 'deleteCorrection']);
    }
}
