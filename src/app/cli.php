<?php

// https://datasets.imdbws.com/



$app = require __DIR__ . '/../lib/RTF.php';

$app->container->set('config', '\RTF\Config');

$app->container->set("db", function($container) {
    return new \RTF\DB($container->config('db/db'), $container->config('db/user'), $container->config('db/pass'), $container->config('db/host'), $container->config('db/charset'));
});






$app->run();