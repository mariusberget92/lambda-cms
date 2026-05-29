<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'   => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'in:1h,6h,24h,7d,30d,permanent'],
        ];
    }
}
