#!/usr/bin/env php
<?php

set_time_limit(0);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use MongoSQL\Console\Command\QueryCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../config'));
$loader->load('services.php');

$app = new Application('Mongo SQL CLI Application', '0.0.1');
$app->add(new QueryCommand($container->get('queryHandler')));
$app->run();
