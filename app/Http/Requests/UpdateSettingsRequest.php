<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $section = $this->route('group');

        return match ($section) {
            'site' => [
                'site\.name' => ['required', 'string', 'max:100'],
                'site\.url' => ['required', 'url', 'max:255'],
            ],
            'locale' => [
                'locale\.timezone' => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
                'locale\.date_format' => ['required', 'string', 'max:20'],
            ],
            'media' => [
                'media\.max_upload_mb' => ['required', 'integer', 'min:1', 'max:500'],
                'media\.resize_max_width' => ['required', 'integer', 'min:100', 'max:5000'],
                'media\.allowed_categories' => ['array'],
                'media\.allowed_categories.*' => ['in:image,document,video,audio'],
                'media\.custom_mimes' => ['array'],
                'media\.custom_mimes.*' => ['string', 'max:100'],
            ],
            'mail' => [
                'mail\.driver' => ['required', 'string', Rule::in(['smtp', 'log', 'mailgun'])],
                'mail\.host' => ['nullable', 'string', 'max:255'],
                'mail\.port' => ['nullable', 'integer'],
                'mail\.username' => ['nullable', 'string', 'max:255'],
                'mail\.password' => ['nullable', 'string', 'max:255'],
                'mail\.from_address' => ['required', 'email'],
                'mail\.from_name' => ['required', 'string', 'max:100'],
                'mail\.encryption' => ['nullable', Rule::in(['tls', 'ssl', ''])],
            ],
            'comments' => [
                'comments\.enabled' => ['required', 'in:0,1'],
                'comments\.per_page' => ['required', 'integer', 'min:5', 'max:100'],
            ],
            'seo' => [
                'seo\.title_separator' => ['required', 'string', 'max:10'],
                'seo\.default_description' => ['nullable', 'string', 'max:300'],
                'seo\.default_og_image_url' => ['nullable', 'url', 'max:500'],
                'seo\.default_keywords' => ['nullable', 'string', 'max:255'],
            ],
            'appearance' => [
                'site\.accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ],
            'code' => [
                'code\.custom_js' => ['nullable', 'string'],
            ],
            default => [],
        };
    }
}
