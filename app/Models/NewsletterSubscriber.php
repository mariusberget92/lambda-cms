<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'token',
        'confirmed_at',
        'ip_address',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }
}
