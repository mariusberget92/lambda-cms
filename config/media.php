<?php

return [
    /*
     * Maximum upload size in megabytes.
     * This will be overridden by the settings system when it is built.
     */
    'max_upload_mb' => env('MEDIA_MAX_UPLOAD_MB', 10),

    /*
     * Allowed MIME types grouped by category.
     */
    'allowed_mimes' => [
        'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'video'    => ['video/mp4', 'video/webm'],
        'audio'    => ['audio/mpeg', 'audio/wav'],
    ],

    /*
     * Image resize width in pixels applied on upload.
     * Images wider than this will be scaled down proportionally.
     * Set to null to disable resizing.
     */
    'resize_max_width' => 1920,
];
