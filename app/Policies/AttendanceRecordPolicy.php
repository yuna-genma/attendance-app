<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Models\Attendance;

class AttendanceRecordPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->admin_status === true) {
            return true;
        }

        return null;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id;
    }

    public function updateCorrection(User $user, AttendanceCorrection $correction): bool
    {
        return $user->id === $correction->user_id;
    }

    public function deleteCorrection(User $user, AttendanceCorrection $correction): bool
    {
        return $user->id === $correction->user_id;
    }
}
