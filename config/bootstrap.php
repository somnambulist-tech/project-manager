<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$envFile = sprintf('%s/%s/.env', $_SERVER['HOME'], $_SERVER['SOMNAMBULIST_PROJECT_MANAGER_DIR'] ?? '.spm_projects.d');

if (file_exists($envFile)) {
    // load all the .env files
    (new Dotenv(false))->loadEnv($envFile);
}

$_SERVER                                     += $_ENV;
$_SERVER['APP_ENV']                          = 'dev';
$_SERVER['APP_DEBUG']                        = true;
$_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'] = dirname($envFile);
