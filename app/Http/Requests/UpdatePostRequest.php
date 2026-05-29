<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'excerpt'            => ['nullable', 'string', 'max:500'],
            'body'               => ['nullable', 'string'],
            'status'             => ['required', 'in:draft,scheduled,published'],
            'published_at'       => [
                Rule::when($this->input('status') === 'scheduled', ['required', 'date', 'after:now']),
                Rule::when($this->input('status') !== 'scheduled', ['nullable']),
            ],
            'category_ids'       => ['nullable', 'array'],
            'category_ids.*'     => ['exists:categories,id'],
            'tag_ids'            => ['nullable', 'array'],
            'tag_ids.*'          => ['exists:tags,id'],
            'new_tag_names'      => ['nullable', 'array', 'distinct'],
            'new_tag_names.*'    => ['string', 'min:1', 'max:50'],
            'featured_image_id'  => ['nullable', 'exists:media,id'],
            'comments_enabled'   => ['nullable', 'boolean'],
            'featured'           => ['nullable', 'boolean'],
            'meta_title'         => ['nullable', 'string', 'max:100'],
            'meta_description'   => ['nullable', 'string', 'max:300'],
            'meta_keywords'      => ['nullable', 'string', 'max:255'],
            'custom_js'          => ['nullable', 'string'],
            'body_format'        => ['nullable', 'in:html,markdown'],
            'use_block_editor'   => ['nullable', 'boolean'],
            'blocks'             => ['nullable', 'array'],
        ];
    }
}
