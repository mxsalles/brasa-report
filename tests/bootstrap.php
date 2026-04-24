<?php

/**
 * Garante APP_ENV=testing antes do autoload, para que o container não leia
 * APP_ENV=local do .env e dispare sementes em AppServiceProvider durante a suíte.
 */
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

define('APP_RUNNING_TESTS', true);

require dirname(__DIR__).'/vendor/autoload.php';
