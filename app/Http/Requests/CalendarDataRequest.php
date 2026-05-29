<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalendarDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => [
                'nullable',
                'date_format:Y-m',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }
                    $year = (int) explode('-', $value)[0];
                    if ($year < 2000 || $year > 2099) {
                        $fail('The year must be between 2000 and 2099.');
                    }
                },
            ],
        ];
    }
}
