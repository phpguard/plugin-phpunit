<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

$basedir = realpath(__DIR__.'/..');
$loader->addPsr4('PhpGuard\\Plugins\\PHPUnit\\Functional\\', $basedir.'/functional');
$loader->register();
