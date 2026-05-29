<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url'      => ['required', 'url', 'max:500'],
            'secret'   => ['nullable', 'string', 'max:255'],
            'events'   => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:post.published,post.updated,post.deleted,page.published,page.updated,page.deleted'],
            'is_active' => ['boolean'],
        ];
    }
}
