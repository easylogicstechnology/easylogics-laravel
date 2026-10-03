<?php

return [
    /*
    | wkhtmltopdf binary used for the Statutory Audit Report PDF (dompdf cannot shape Devanagari). Set
    | WKHTMLTOPDF_BINARY in .env to the full path (Windows: the wkhtmltopdf.exe, and install Noto Sans Devanagari
    | as a system font; Linux: install the `wkhtmltopdf` and `fonts-noto-devanagari` packages).
    */
    'wkhtmltopdf' => env('WKHTMLTOPDF_BINARY', 'wkhtmltopdf'),
];
