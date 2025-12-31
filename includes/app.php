<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package Includes
 */
require __DIR__ . '/../vendor/autoload.php';

use App\Config\src\Environment;
use App\Config\src\Database;
use App\Http\Middleware\Queue;
use App\Utils\View;
use App\Utils\DebugBar;
use App\Utils\Logger;
use App\Utils\Cache;
use App\Exceptions\ExceptionHandler;
use App\Database\AutoMigrate;
use App\Utils\Session;
use App\Utils\CSRF;

Environment::load(__DIR__ . '/../');

Logger::init(__DIR__ . '/../storage/logs', getenv('LOG_LEVEL') ?: 'DEBUG');

$cacheDriver = getenv('CACHE_DRIVER') ?: 'file';
$dbHost = getenv('DB_HOST') ?: null;
$dbName = getenv('DB_NAME') ?: null;
$dbUser = getenv('DB_USER') ?: null;
$dbPass = getenv('DB_PASS') ?: null;
$dbPort = getenv('DB_PORT') ?: 3306;
$cacheConfig = [];

if ($cacheDriver === 'redis') {
    $cacheConfig = [
        'host' => getenv('REDIS_HOST') ?: 'localhost',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'password' => getenv('REDIS_PASS') ?: null
    ];
} elseif ($cacheDriver === 'memcached') {
    $cacheConfig = [
        'host' => getenv('MEMCACHED_HOST') ?: 'localhost',
        'port' => getenv('MEMCACHED_PORT') ?: 11211
    ];
} else {
    $cacheConfig = [
        'path' => __DIR__ . '/../storage/cache'
    ];
}

Cache::init($cacheDriver, $cacheConfig);
ExceptionHandler::init();
DebugBar::init();
Session::init();
CSRF::initialize();
AutoMigrate::run();

if ($dbHost && $dbName && $dbUser) {
    Database::config($dbHost, $dbName, $dbUser, $dbPass, $dbPort);
}

define('URL', getenv('URL'));
define('NAME_APP', getenv('NAME_APP'));
define('APP_DEBUG', getenv('APP_DEBUG'));
define('EMAIL', getenv('EMAIL'));
define('URLSUB', getenv('URLSUB'));
define('JWT', getenv('JWT'));
define('MAIL_HOST', getenv('MAIL_HOST'));
define('MAIL_PORT', getenv('MAIL_PORT'));
define('MAIL_USERNAME', getenv('MAIL_USERNAME'));
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME'));
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL'));
define('MAIL_SECURE', getenv('MAIL_SECURE'));
define('CACHE_DRIVER', getenv('CACHE_DRIVER'));
define('CACHE_TIME', getenv('CACHE_TIME'));
define('CACHE_DIR', getenv('CACHE_DIR'));
define('REDIS_HOST', getenv('REDIS_HOST'));
define('REDIS_PORT', getenv('REDIS_PORT'));
define('REDIS_PASS', getenv('REDIS_PASS'));
define('STORAGE_DRIVER', getenv('STORAGE_DRIVER'));
define('S3_KEY', getenv('S3_KEY'));
define('S3_SECRET', getenv('S3_SECRET'));
define('S3_REGION', getenv('S3_REGION'));
define('S3_BUCKET', getenv('S3_BUCKET'));
define('S3_BASE_PATH', getenv('S3_BASE_PATH'));
define('S3_ENDPOINT', getenv('S3_ENDPOINT'));


View::init([
    'URL' => URL,
    'NAME_APP' => NAME_APP,
    'APP_DEBUG' => APP_DEBUG,
    'JWT' => JWT,
    'EMAIL' => EMAIL,
    'URLSUB' => URLSUB,
    'MAIL_HOST' => MAIL_HOST,
    'MAIL_PORT' => MAIL_PORT,
    'MAIL_USERNAME' => MAIL_USERNAME,
    'MAIL_PASSWORD' => MAIL_PASSWORD,
    'MAIL_FROM_NAME' => MAIL_FROM_NAME,
    'MAIL_FROM_EMAIL' => MAIL_FROM_EMAIL,
    'MAIL_SECURE' => MAIL_SECURE,
    'CACHE_DRIVER' => CACHE_DRIVER,
    'CACHE_TIME' => CACHE_TIME,
    'CACHE_DIR' => CACHE_DIR,
    'REDIS_HOST' => REDIS_HOST,
    'REDIS_PORT' => REDIS_PORT,
    'REDIS_PASS' => REDIS_PASS,
    'STORAGE_DRIVER' => STORAGE_DRIVER,
    'S3_KEY' => S3_KEY,
    'S3_SECRET' => S3_SECRET,
    'S3_REGION' => S3_REGION,
    'S3_BUCKET' => S3_BUCKET,
    'S3_BASE_PATH' => S3_BASE_PATH,
    'S3_ENDPOINT' => S3_ENDPOINT,
    'generated_at' => date('H:i:s'),

]);


Queue::setMap([
    'debug' => \App\Http\Middleware\DebugMiddleware::class,
    'maintenance' => \App\Http\Middleware\Maintenance::class,
    'maintenanceMode' => \App\Http\Middleware\MaintenanceModeMiddleware::class,
    'cache' => App\Http\Middleware\Cache::class,
    'api' => App\Http\Middleware\Api::class,
]);

Queue::setDefault([
    'debug',
    'maintenance',
    'maintenanceMode'
]);

// Inicializar sistema de documentação embutido
$appDocs = getenv('APP_DOCS') === 'true' || getenv('APP_DOCS') === '1';
if ($appDocs && file_exists(__DIR__ . '/../app/Utils/Documentation.php')) {
    require_once __DIR__ . '/../app/Config/documentation.php';
    Logger::debug('Documentation system initialized', [
        'components' => count(\App\Utils\Documentation::list())
    ]);
}
