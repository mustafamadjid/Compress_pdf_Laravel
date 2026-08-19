<?php

return [
    'max_upload_mb' => (int) env('PDF_COMPRESSOR_MAX_UPLOAD_MB', 25),
    'retention_minutes' => (int) env('PDF_COMPRESSOR_RETENTION_MINUTES', 60),
    'process_timeout' => (int) env('PDF_COMPRESSOR_PROCESS_TIMEOUT', 120),
    'ghostscript_binary' => env('GHOSTSCRIPT_BINARY', 'gs'),
];
