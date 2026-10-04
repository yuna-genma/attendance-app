<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class DateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date',
        ];
    }

    protected function passedValidation()
    {
        if ($this->filled('date')) {
            $this->merge([
                'validated_date' => Carbon::parse($this->input('date'))->startOfDay(),
            ]);
        } else {
            $this->merge([
                'validated_date' => Carbon::today(),
            ]);
        }
    }
}
