<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer',
            'date' => 'nullable|date',
            'month' => 'nullable|string|date_format:Y-m',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer|max:100',
        ];
    }
}
