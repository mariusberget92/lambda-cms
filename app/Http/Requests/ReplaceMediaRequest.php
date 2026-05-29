<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) (config('media.max_upload_mb', 10) * 1024);

        return [
            'file' => ['required', 'file', "max:{$maxKb}", 'mimetypes:image/jpeg,image/png,image/webp,image/gif'],
        ];
    }
}
