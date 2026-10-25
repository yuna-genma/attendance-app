<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_clock_in' => 'required|date_format:H:i',
            'new_clock_out' => 'nullable|date_format:H:i',
            'new_break_in' => 'nullable|array',
            'new_break_out' => 'nullable|array',
            'new_break_in.*' => 'nullable|date_format:H:i',
            'new_break_out.*' => 'nullable|date_format:H:i',
            'comment' => 'required|string|max:255',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->any()) {
                    return;
                }

                $clockIn = $this->input('new_clock_in');
                $clockOut = $this->input('new_clock_out');

                if ($clockOut && $clockIn > $clockOut) {
                    $validator->errors()->add(
                        'new_clock_in',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }

                $breakIns = $this->input('new_break_in', []);
                $breakOuts = $this->input('new_break_out', []);

                foreach ($breakIns as $index => $breakIn) {
                    $breakOut = $breakOuts[$index] ?? null;

                    if (is_null($breakIn) || $breakIn === '') {
                        continue;
                    }
                    if ($breakOut === '') {
                        $breakOut = null;
                    }

                    if ($breakIn < $clockIn) {
                        $validator->errors()->add(
                            "new_break_in.{$index}",
                            "休憩時間が不適切な値です"
                        );
                    }

                    if ($clockOut) {
                        if ($breakIn > $clockOut) {
                            $validator->errors()->add(
                                "new_break_in.{$index}",
                                "休憩時間が不適切な値です",
                            );
                        }
                        if ($breakOut && $breakOut > $clockOut) {
                            $validator->errors()->add(
                                "new_break_out.{$index}",
                                "休憩時間もしくは退勤時間が不適切な値です"
                            );
                        }
                    }
                }
            }
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください'
        ];
    }
}
