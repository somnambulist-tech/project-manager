<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$envFile = sprintf('%s/%s/.env', $_SERVER['HOME'], $_SERVER['SOMNAMBULIST_PROJECT_MANAGER_DIR'] ?? '.spm_projects.d');

if (file_exists($envFile)) {
    // load all the .env files
    (new Dotenv(false))->loadEnv($envFile);
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV']   = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

$_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'] = dirname($envFile);
