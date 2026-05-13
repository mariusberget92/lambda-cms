<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_name',
        'page_slug',
        'data',
        'ip_address',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
