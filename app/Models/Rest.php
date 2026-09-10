<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;
use App\Models\RestCorrection;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_in',
        'break_out',
    ];

    public function attendances()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function restCorrections()
    {
        return $this->hasMany(RestCorrection::class);
    }
}
