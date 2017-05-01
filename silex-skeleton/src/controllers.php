<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

//require_once('config.php');

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});

/* Custom */

$app->get('/cars/{manufacturer}/{model}', function (Request $request, $manufacturer, $model) {

    $manufacturer = preg_replace("/[^0-9a-zA-Z ]/", "", $manufacturer);
    $model = preg_replace("/[^0-9a-zA-Z ]/", "", $model);

    $filename = md5(microtime());

    echo shell_exec("sudo /usr/bin/python2.7 /var/www/html/auto_scrapy/auto/auto/spiders/auto_spider.py ".$manufacturer." ".$model."");


    return new Response('Thank you for your feedback!', 200);
});