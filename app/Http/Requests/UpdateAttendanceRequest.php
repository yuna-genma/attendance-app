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
            'new_clock_out' => 'nullable|date_format:H:i|after:new_clock_in',
            'new_break_in' => 'nullable|array',
            'new_break_out' => 'nullable|array',
            'new_break_in.*' => 'nullable|date_format:H:i',
            'new_break_out.*' => 'nullable|date_format:H:i',
            'comment' => 'nullable|string|max:255',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $clockIn = $this->input('new_clock_in');
                $clockOut = $this->input('new_clock_out');

                if (!$clockIn) {
                    return;
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
                            "休憩開始時間は出勤時間({$clockIn})以降の時間を入力してください"
                        );
                        if ($clockOut) {
                            if ($breakIn > $clockOut) {
                                $validator->errors()->add(
                                    "new_break_in.{$index}",
                                    "休憩開始時間は退勤時間({$clockOut})より前の時間を入力してください",
                                );
                            }
                            if ($breakOut && $breakOut > $clockOut) {
                                $validator->errors()->add(
                                    "new_break_out.{$index}",
                                    "休憩終了時間は退勤時間({$clockOut})より前の時間を入力してください"
                                );
                            }
                            if ($breakOut && $breakOut < $breakIn) {
                                $validator->errors()->add(
                                    "new_break_out.{$index}",
                                    "休憩終了時間は休憩開始時間({$breakIn})以降の時間を入力してください"
                                );
                            }
                        }
                    }
                }
            }
        ];
    }
}
