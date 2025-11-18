<?php

return [
    // Paper size and orientation defaults
    'paper' => env('DOMPDF_DEFAULT_PAPER', 'a4'),
    'orientation' => env('DOMPDF_DEFAULT_ORIENTATION', 'portrait'),

    // Dompdf options (see Dompdf\Options)
    'options' => [
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => env('DOMPDF_REMOTE_ENABLED', false),
        'defaultPaperSize' => env('DOMPDF_DEFAULT_PAPER', 'a4'),
        'defaultFont' => env('DOMPDF_DEFAULT_FONT', 'sans-serif'),
        'dpi' => (int) env('DOMPDF_DPI', 96),
        'isPhpEnabled' => false,
        'isFontSubsettingEnabled' => true,
        'isJavascriptEnabled' => false,
        'chroot' => [
            public_path(),
            storage_path('app/public'),
            resource_path('views'),
        ],
        'tempDir' => storage_path('framework/cache/dompdf'),
        'fontDir' => storage_path('fonts'),
        'fontCache' => storage_path('fonts'),
        // Security-related
        'enable_remote' => env('DOMPDF_REMOTE_ENABLED', false),
        'enable_css_float' => true,
        'enable_html5_parser' => true,
    ],
];
