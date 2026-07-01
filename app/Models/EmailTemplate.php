<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'merge_tags' => 'array',
        ];
    }

    public function resetToDefault(): void
    {
        $this->update([
            'subject' => $this->default_subject,
            'body' => $this->default_body,
        ]);
    }

    public function render(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $tag = '{{'.$key.'}}';
            $subject = str_replace($tag, $value, $subject);
            $body = str_replace($tag, $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
