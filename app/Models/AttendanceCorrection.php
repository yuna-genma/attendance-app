<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CorrectionStatus;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => CorrectionStatus::class,
    ];
}
