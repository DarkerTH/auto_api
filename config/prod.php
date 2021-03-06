<?php

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

$app['api.carsPath'] = __DIR__.'/../var/cars';
$app['api.crawlerPath'] = dirname(__DIR__).'/vendor/darkerth/auto_scrapy/auto/spiders';
$app['api.key'] = 'k12h3gnc98vbzcx87ASDvcxzbxcFDNSKJ897'; //Change this asap!
$app['api.adsCacheTime'] = 5*60; // cache time in seconds
$app['api.brandsCacheTime'] = 24*60*60; // cache time in seconds
$app['api.modelsCacheTime'] = 24*60*60; // cache time in seconds
