<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Responsive image variants
    |--------------------------------------------------------------------------
    |
    | Candidate widths (px) generated beside every uploaded image as
    | `<name>-<width>w.webp`. Only widths strictly smaller than the stored
    | image are produced, so small images never get upscaled or duplicated;
    | the stored image itself is the largest `srcset` candidate.
    |
    */

    'variant_widths' => [320, 480, 640, 750, 1024, 1280, 1600],

    'variant_quality' => 75,

    /*
    |--------------------------------------------------------------------------
    | `sizes` presets
    |--------------------------------------------------------------------------
    |
    | Layout-specific `sizes` values used by server-rendered images.
    |
    */

    'sizes' => [
        'content' => '(min-width: 992px) 800px, 100vw',
        'cover' => '(min-width: 1200px) 1140px, 100vw',
    ],

];
