<?php

defined("LOADED") || define("LOADED", true);
defined("BASE_PATH") || define("BASE_PATH", dirname(__DIR__));

require_once dirname(__DIR__) . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
$dotenv->load();

$request = JobMarket\Http\Request::createFromGlobals();

$kernel = new \JobMarket\Http\Kernel();

$response = $kernel->handler($request);

$response->send();
