<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->input('mailer') === 'smtp') {
            return [
                'mailer'       => ['required', 'in:smtp,log'],
                'host'         => ['required', 'string'],
                'port'         => ['required', 'integer', 'min:1', 'max:65535'],
                'username'     => ['required', 'string'],
                'password'     => ['nullable', 'string'],
                'from_address' => ['required', 'email'],
                'from_name'    => ['required', 'string', 'max:255'],
            ];
        }

        return [
            'mailer'       => ['required', 'in:smtp,log'],
            'from_address' => ['required', 'email'],
            'from_name'    => ['required', 'string', 'max:255'],
        ];
    }
}
