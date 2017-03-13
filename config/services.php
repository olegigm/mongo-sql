<?php
/** @var $container Symfony\Component\DependencyInjection\ContainerBuilder */

use Symfony\Component\DependencyInjection\Reference;

$container->register('sqlParser', 'PHPSQLParser\PHPSQLParser');
$container->register('mongoClient', 'MongoDB\Client');

$container
    ->register('queryHandler', 'MongoSQL\Service\QueryHandler')
    ->addArgument(new Reference('sqlParser'))
    ->addArgument(new Reference('mongoClient'));
