<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;
use App\Models\RestCorrection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_in',
        'break_out',
    ];

    public function attendances(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function restCorrections(): HasMany
    {
        return $this->hasMany(RestCorrection::class);
    }

    protected function breakIn(): Attribute
    {
        return Attribute::get(fn($value) => $value ? Carbon::parse($value)->format('H:i') : '');
    }

    protected function breakOut(): Attribute
    {
        return Attribute::get(fn($value) => $value ? Carbon::parse($value)->format('H:i') : '');
    }
}
