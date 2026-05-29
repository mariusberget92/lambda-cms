<?php

namespace App\Services;

use App\Models\Template;

class TemplateResolver
{
    /** @var array<string, Template|null> */
    private array $cache = [];

    public function resolve(string $type): ?Template
    {
        if (! array_key_exists($type, $this->cache)) {
            $this->cache[$type] = Template::activeFor($type);
        }

        return $this->cache[$type];
    }
}
