<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutosaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'array'],
        ];
    }
}
