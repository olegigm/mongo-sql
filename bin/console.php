#!/usr/bin/env php
<?php

set_time_limit(0);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use MongoSQL\Console\Command\QueryCommand;

$app = new Application('Mongo SQL CLI Application', '0.0.1');
$app->add(new QueryCommand());
$app->run();
