<?php

// configure your app for the production environment

$app->register(new Silex\Provider\SessionServiceProvider());

$app['twig.path'] = array(__DIR__ . '/../templates');
$app['twig.options'] = array(
    'cache' => false,

);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => 'bss',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8',
    ),
));