<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->input('driver') === 'mysql') {
            return [
                'driver'   => ['required', 'in:sqlite,mysql'],
                'host'     => ['required', 'string'],
                'port'     => ['required', 'integer', 'min:1', 'max:65535'],
                'database' => ['required', 'string'],
                'username' => ['required', 'string'],
                'password' => ['nullable', 'string'],
                'prefix'   => ['nullable', 'string'],
            ];
        }

        return [
            'driver' => ['required', 'in:sqlite,mysql'],
        ];
    }
}
