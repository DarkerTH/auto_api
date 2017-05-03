<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
    ->bind('homepage');

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/' . $code . '.html.twig',
        'errors/' . substr($code, 0, 2) . 'x.html.twig',
        'errors/' . substr($code, 0, 1) . 'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});

/* Custom */

$app->get('/cars/{manufacturer}/{model}', function (Request $request, $manufacturer, $model) use ($app) {
    if ($request->query->get('key') !== $app['api.key']) {
        return $app->json([
            'errors' => [
                [
                    'message' => 'Access forbidden.',
                ]
            ]
        ], 401);
    }

    $manufacturer = preg_replace("/[^0-9a-zA-Z ]/", "", $manufacturer);
    $model = preg_replace("/[^0-9a-zA-Z ]/", "", $model);

    $year_from = $request->query->get('year_from') ? $request->query->get('year_from') : 1900;
    $year_to = $request->query->get('year_to') ? $request->query->get('year_to') : 2018;
    $price_from = $request->query->get('price_from') ? $request->query->get('price_from') : 0;
    $price_to = $request->query->get('price_to') ? $request->query->get('price_to') : 200000;

    $cars_path = $app['api.carsPath'];
    $crawler_path = $app['api.crawlerPath'];


    if (!file_exists($cars_path)) {
        mkdir($cars_path, 0777);
    }

    $file_name_md5 = md5($manufacturer . $model . $year_from . $year_to . $price_from . $price_to);
    $file_path = $cars_path . '/' . $file_name_md5 . '.json';

    if (file_exists($file_path)) {
        $file_modified_at = filemtime($file_path);

        if (time() - $file_modified_at < $app['api.cacheTime']) {
            $get_file = json_decode(file_get_contents($file_path));
            return $app->json($get_file, 200);
        }

    }

    $crawl = trim(shell_exec("sudo /usr/bin/python2.7 " . $crawler_path . " " . $file_name_md5 . " " . $manufacturer . " " . $model . " " . $year_from . " " . $year_to . " " . $price_from . " " . $price_to));
    file_put_contents($file_path, $crawl);

    if (file_exists($file_path)) {
        $get_file = json_decode(file_get_contents($file_path));
        return $app->json($get_file, 200);
    }

    return $app->json([
        'success' => 0,
        'message' => "Unable to fetch data.",
    ], 503);
});
