<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage
    |--------------------------------------------------------------------------
    | Default storage configuration when not using Spatie Media Library.
    */
    'storage' => [
        'disk' => env('FILAMENT_IMAGE_EDITOR_DISK', 'public'),
        'directory' => env('FILAMENT_IMAGE_EDITOR_DIR', 'images'),
        'visibility' => 'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Output Settings
    |--------------------------------------------------------------------------
    */
    'output' => [
        'format' => 'jpeg', // jpeg, png, webp
        'quality' => 0.92,
        'max_width' => null,
        'max_height' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tools
    |--------------------------------------------------------------------------
    */
    'tools' => ['crop', 'filter', 'draw'],

    /*
    |--------------------------------------------------------------------------
    | Crop Settings
    |--------------------------------------------------------------------------
    */
    'crop' => [
        'aspect_ratios' => [
            'free' => null,
            '1:1' => 1,
            '4:3' => 4 / 3,
            '3:2' => 3 / 2,
            '16:9' => 16 / 9,
            '9:16' => 9 / 16,
        ],
        'default_ratio' => 'free',
        'min_width' => 10,
        'min_height' => 10,
        'enable_rotation' => true,
        'enable_flip' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Settings
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'presets' => [
            'original',
            'grayscale',
            'sepia',
            'vintage',
            'warm',
            'cool',
            'dramatic',
            'fade',
            'vivid',
        ],
        'adjustments' => [
            'brightness',
            'contrast',
            'saturation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Drawing Settings
    |--------------------------------------------------------------------------
    */
    'draw' => [
        'tools' => ['select', 'freehand', 'eraser', 'line', 'arrow', 'rectangle', 'ellipse', 'text'],
        'default_stroke_color' => '#000000',
        'default_stroke_width' => 4,
        'default_fill_color' => 'transparent',
        'color_palette' => [
            'transparent', '#FFFFFF', '#C0C0C0', '#808080', '#000000',
            '#000080', '#0000FF', '#00FFFF', '#008080', '#808000',
            '#00FF00', '#FFFF00', '#FFA500', '#FF0000', '#800000',
            '#FF00FF', '#800080',
        ],
        'fonts' => [
            'Arial', 'Helvetica', 'Georgia', 'Times New Roman',
            'Courier New', 'Verdana', 'Trebuchet MS',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | History Settings
    |--------------------------------------------------------------------------
    */
    'history' => [
        'limit' => 50,
        'keyboard_shortcuts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'modal_size' => '6xl',
        'open_on_select' => true,
        'show_zoom_controls' => true,
        'preview_max_size' => 2000, // Downsample images larger than this for editing
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Defaults
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'accepted_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'max_file_size' => 10 * 1024, // 10MB in KB
    ],
];
