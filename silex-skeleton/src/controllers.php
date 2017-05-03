<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));
$app['debug'] = true;

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

$app->get('/cars/{manufacturer}/{model}', function (Request $request, $manufacturer, $model) use ($app) {
    if ($request->query->get('key') !== "k12h3gnc98vbzcx87ASDvcxzbxcFDNSKJ897") {
        return $app->json([
            'success' => 0,
            'message' => 'Access forbidden.',
        ], 403);
    }

    $manufacturer = preg_replace("/[^0-9a-zA-Z ]/", "", $manufacturer);
    $model = preg_replace("/[^0-9a-zA-Z ]/", "", $model);

    $file_name_md5 = md5($manufacturer.$model);
    $file_path = $_SERVER['DOCUMENT_ROOT'].$request->getBasePath().'/'.$file_name_md5.'.json';

    if (file_exists($file_path)) {
        $file_modified_at = filemtime($file_path);

        if (time() - $file_modified_at < 1*300){
            $get_file = json_decode(file_get_contents($file_path));
            return $app->json($get_file, 200);
        }

        echo shell_exec('sudo rm -f '.$file_path);

    }

    $crawl = trim(shell_exec("sudo /usr/bin/python2.7 /var/www/html/auto_scrapy/auto/auto/spiders/auto_spider.py ".$file_name_md5." ".$manufacturer." ".$model));

    if (file_exists($file_path)) {
        $get_file = json_decode(file_get_contents($file_path));
        return $app->json($get_file, 200);
    }

    return $app->json([
        'success' => 0,
        'message' => "Unable to fetch data.",
    ], 503);
});
