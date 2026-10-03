<?php

namespace App\Support;

/** Stand-in for CakePHP's $this->Html in the carried-over bill views: image() only. The picture files sit under img/ (CakePHP's webroot/img). */
class LegacyHtml
{
    public function image($path, array $options = [])
    {
        $attrs = '';
        foreach ($options as $k => $v) {
            if (in_array($k, ['height', 'width', 'style', 'alt', 'class'], true)) {
                $attrs .= ' ' . $k . '="' . e($v) . '"';
            }
        }

        return '<img src="' . e(asset('img/' . ltrim((string) $path, '/'))) . '"' . $attrs . '>';
    }
}