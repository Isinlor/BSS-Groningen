<?php

// configure your app for the production environment

$app->register(new Silex\Provider\SessionServiceProvider());

$app['twig.path'] = array(__DIR__ . '/../templates');
$app['twig.options'] = array(
    'cache' => false,

);
