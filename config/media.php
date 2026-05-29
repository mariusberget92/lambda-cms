<?php

use App\Models\Setting;

/*
 * Helper: safely call Setting::get(), returning $fallback if the settings
 * table does not yet exist (pre-migration) or any other error occurs.
 */
$settingGet = function (string $key, mixed $fallback): mixed {
    try {
        return Setting::get($key, $fallback);
    } catch (Throwable) {
        return $fallback;
    }
};

return [
    /*
     * Maximum upload size in megabytes.
     * Primary source: DB-backed Setting 'media.max_upload_mb'.
     * Fallback: MEDIA_MAX_UPLOAD_MB env var, then 10.
     */
    'max_upload_mb' => $settingGet('media.max_upload_mb', env('MEDIA_MAX_UPLOAD_MB', 10)),

    /*
     * Allowed MIME types — resolved dynamically from DB-backed settings.
     * Categories enabled via 'media.allowed_categories'; extra types via 'media.custom_mimes'.
     */
    /*
     * Grouped MIME types by category — used by Media::typeFromMime() to determine
     * a file's type label ('image', 'document', 'video', 'audio') from its MIME type.
     */
    'mime_groups' => [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'video' => ['video/mp4', 'video/webm'],
        'audio' => ['audio/mpeg', 'audio/wav'],
    ],

    'allowed_mimes' => (function () use ($settingGet): array {
        $groups = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'video' => ['video/mp4', 'video/webm'],
            'audio' => ['audio/mpeg', 'audio/wav'],
        ];

        $enabledCategories = json_decode($settingGet('media.allowed_categories', json_encode(array_keys($groups))), true);
        if (empty($enabledCategories)) {
            $enabledCategories = array_keys($groups);
        }

        $customMimes = json_decode($settingGet('media.custom_mimes', '[]'), true) ?? [];

        $allowed = [];
        foreach ($enabledCategories as $cat) {
            if (isset($groups[$cat])) {
                $allowed = array_merge($allowed, $groups[$cat]);
            }
        }

        return array_values(array_unique(array_merge($allowed, (array) $customMimes)));
    })(),

    /*
     * Image resize width in pixels applied on upload.
     * Primary source: DB-backed Setting 'media.resize_max_width'.
     * Fallback: 1920.
     * Images wider than this will be scaled down proportionally.
     * Set to null to disable resizing.
     */
    'resize_max_width' => $settingGet('media.resize_max_width', 1920),
];
