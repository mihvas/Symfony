<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (!class_exists(Dotenv::class)) {
    return;
}

if (!isset($_SERVER['APP_ENV'])) {
    putenv('APP_ENV=' . ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev'));
    $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = getenv('APP_ENV');
}

(new Dotenv())
    ->usePutenv(true)
    ->bootEnv(__DIR__ . '/../.env', $_SERVER['APP_ENV']);
