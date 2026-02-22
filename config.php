<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('LINE_CLIENT_ID', $_ENV['LINE_CLIENT_ID']);
define('LINE_CLIENT_SECRET', $_ENV['LINE_CLIENT_SECRET']);
define('LINE_REDIRECT_URI', $_ENV['LINE_REDIRECT_URI']);

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('PLATFORM_KEY', $_ENV['PLATFORM_KEY']);
