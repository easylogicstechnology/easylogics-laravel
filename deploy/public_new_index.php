<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// This file is the web entry point (e.g. <site root>/new/index.php).
// Laravel lives OUTSIDE the web root, in a folder named easylogics-laravel:
//   CloudPanel : /home/societynew1/htdocs/easylogics-laravel  (site: /home/societynew1/htdocs/societynew.in/new)
//   Hostinger  : /home/u.../easylogics-laravel                (site: /home/u.../public_html/new)
$laravelBase = null;
foreach ([dirname(__DIR__, 2), dirname(__DIR__, 1), dirname(__DIR__, 3)] as $dir) {
    if (is_file($dir . '/easylogics-laravel/vendor/autoload.php')) {
        $laravelBase = $dir . '/easylogics-laravel';
        break;
    }
}
if ($laravelBase === null) {
    http_response_code(500);
    exit('Laravel base folder "easylogics-laravel" not found next to the web root.');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $laravelBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $laravelBase.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $laravelBase.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
