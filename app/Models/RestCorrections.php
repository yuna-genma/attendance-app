<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CorrectionStatus;
use App\Models\Admin;
use App\Models\Rest;

class RestCorrections extends Model
{
    use HasFactory;

    protected $fillable = [
        'rest_id',
        'admin_id',
        'new_break_in',
        'new_break_out',
        'status',
    ];

    protected $casts = [
        'status' => CorrectionStatus::class,
    ];

    public function rest()
    {
        return $this->belongsTo(Rest::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
