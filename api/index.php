<?php

// // Allow from any origin
// if (isset($_SERVER['HTTP_ORIGIN'])) {
//     // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
//     // you want to allow, and if so:
//     header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
//     header('Access-Control-Allow-Credentials: true');
//     header('Access-Control-Max-Age: 86400');    // cache for 1 day
// }

// // Access-Control headers are received during OPTIONS requests
// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//     if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
//         // may also be using PUT, PATCH, HEAD etc
//         header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
//     }

//     if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
//         header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
//     }

//     exit(0);
// }

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
# Session lifetime of 20 hours
ini_set('session.gc_maxlifetime', 20 * 60 * 60);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
if (!file_exists(__DIR__ . '/sessions')) {
    mkdir(__DIR__ . '/sessions', 0777, true);
}

date_default_timezone_set("Asia/Jakarta");

session_save_path(__DIR__ . '/sessions');
session_start();

require 'vendor/autoload.php';

/* --- System --- */
require 'systems/gump-validation/gump.class.php';
require 'systems/domain.php';
require 'systems/database.php';
require 'systems/systems.php';
require 'systems/functions.php';
if (file_exists('vendor/cahkampung/landa-php/src/LandaPhp.php')) {
    require 'vendor/cahkampung/landa-php/src/LandaPhp.php';
}
if (file_exists('acc/landaacc/functions.php')) {
    require 'acc/landaacc/functions.php';
}

$config = [
    'displayErrorDetails'               => config('DISPLAY_ERROR'),
    'determineRouteBeforeAppMiddleware' => true,
];

$app = new \Slim\App(["settings" => $config]);

require 'systems/dependencies.php';
require 'systems/middleware.php';

/** route to php file */
$file = getUrlFile();
require $file;

$app->run();
