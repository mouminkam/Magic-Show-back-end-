<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Quality
    |--------------------------------------------------------------------------
    |
    | Default quality for compressed images (0-100)
    | 85 is the sweet spot between quality and file size
    |
    */
    'quality' => env('IMAGE_QUALITY', 85),

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | Define different image sizes to be generated automatically
    | Set width/height to null to maintain aspect ratio
    |
    */
    'sizes' => [
        'original' => [
            'width' => null,  // Maintains original width
            'height' => null, // Maintains original height
        ],
        'banner' => [
            'width' => 1920,
            'height' => null,
        ],
        'large' => [
            'width' => 1200,
            'height' => null, // Maintains aspect ratio
        ],
        'medium' => [
            'width' => 600,
            'height' => null, // Maintains aspect ratio
        ],
        'thumbnail' => [
            'width' => 150,
            'height' => 150,  // Square thumbnail
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebP Support
    |--------------------------------------------------------------------------
    |
    | Enable WebP format for better compression (30% smaller than JPEG)
    | Requires GD or Imagick extension
    |
    */
    'webp' => [
        'enabled' => env('IMAGE_WEBP_ENABLED', true),
        'quality' => env('IMAGE_WEBP_QUALITY', 85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermark Settings
    |--------------------------------------------------------------------------
    |
    | Add watermark to images (optional)
    |
    */
    'watermark' => [
        'enabled' => env('IMAGE_WATERMARK_ENABLED', false),
        'path' => env('IMAGE_WATERMARK_PATH', 'watermark.png'),
        'position' => env('IMAGE_WATERMARK_POSITION', 'bottom-right'),
        'opacity' => env('IMAGE_WATERMARK_OPACITY', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk to use for saving images
    |
    */
    'disk' => env('IMAGE_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Extensions
    |--------------------------------------------------------------------------
    |
    | Image file extensions allowed for upload
    |
    */
    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | Maximum file size in kilobytes (default: 10MB)
    |
    */
    'max_size' => env('IMAGE_MAX_SIZE', 10240), // 10MB

];
