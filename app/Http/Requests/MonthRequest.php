<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class MonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date_format:Y-m'
        ];
    }

    protected function passedValidation()
    {
        $dateString = $this->query('date');

        try {
            $date = $dateString ? Carbon::parse($dateString)->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Exception $e) {
            $date = Carbon::today()->startOfMonth();
        }

        $this->merge([
            'validated_date' => $date,
        ]);
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $this->merge([
            'validated_date' => Carbon::today()->startOfMonth(),
        ]);
    }
}
