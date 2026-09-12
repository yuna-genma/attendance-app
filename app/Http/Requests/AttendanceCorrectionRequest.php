<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;

class AttendanceCorrectionRequest extends FormRequest
{

    public function authorize(): bool
    {
        $attendanceId = $this->route('id');
        $attendance = Attendance::find($attendanceId);

        return $attendance && $attendance->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'new_clock_in' => 'required|date_format:H:i',
            'new_clock_out' => 'nullable|date_format:H:i|after:new_clock_in',
            'new_break_in' => 'nullable|array',
            'new_break_out' => 'nullable|array',
            'new_break_in.*' => 'nullable|date_format:H:i',
            'new_break_out.*' => 'nullable|date_format:H:i|after:new_break_in.*',
            'comment' => 'nullable|string|max:255',
        ];
    }
}
