<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$confDir = $_SERVER['SOMNAMBULIST_PROJECT_MANAGER_DIR'] ?? 'spm_projects.d';
$homeDir = $_SERVER['XDG_CONFIG_HOME'] ?? $_SERVER['HOME'];
$envFile = null;

foreach ([$confDir, 'spm_projects.d', '.config/spm_projects.d', '.spm_projects.d'] as $test) {
    if (file_exists($envFile = sprintf('%s/%s/.env', $homeDir, $test))) {
        break;
    }
}

if (!is_null($envFile)) {
    // load all the .env files
    (new Dotenv(false))->loadEnv($envFile);
} else {
    if (!in_array('init', $_SERVER['argv'])) {
        echo "\n ** Project Manager has not been initialised, run spm init **\n\n";
    }
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV']   = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

$_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'] = dirname($envFile);
$_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] = $_ENV['SOMNAMBULIST_ACTIVE_PROJECT'] = ($_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] ?? $_ENV['SOMNAMBULIST_ACTIVE_PROJECT'] ?? null) ?: null;
$_SERVER['PROJECT_LIBRARIES_DIR'] = $_ENV['PROJECT_LIBRARIES_DIR'] = ($_SERVER['PROJECT_LIBRARIES_DIR'] ?? $_ENV['PROJECT_LIBRARIES_DIR'] ?? null) ?: null;
$_SERVER['PROJECT_SERVICES_DIR'] = $_ENV['PROJECT_SERVICES_DIR'] = ($_SERVER['PROJECT_SERVICES_DIR'] ?? $_ENV['PROJECT_SERVICES_DIR'] ?? null) ?: null;
