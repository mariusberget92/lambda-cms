<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }

    public function rules(): array
    {
        $maxKb    = (int) (config('media.max_upload_mb', 10) * 1024);
        $allMimes = collect(config('media.allowed_mimes', []))->flatten()->implode(',');

        return [
            'file' => ['required', 'file', "max:{$maxKb}", "mimetypes:{$allMimes}"],
        ];
    }
}
