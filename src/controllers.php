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

    $year_from = $request->query->get('year_from') ? preg_replace('/\D/', '', $request->query->get('year_from')) : 1900;
    $year_to = $request->query->get('year_to') ? preg_replace('/\D/', '', $request->query->get('year_to')) : 2018;
    $price_from = $request->query->get('price_from') ? preg_replace('/\D/', '', $request->query->get('price_from')) : 0;
    $price_to = $request->query->get('price_to') ? preg_replace('/\D/', '', $request->query->get('price_to')) : 200000;

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
            $get_file = file_get_contents($file_path);
            $response = new Response($get_file, 200);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            return $response;
        }

    }

    $crawl = trim(shell_exec("sudo /usr/bin/python2.7 " . $crawler_path . " " . $file_name_md5 . " " . $manufacturer . " " . $model . " " . $year_from . " " . $year_to . " " . $price_from . " " . $price_to));
    $codepoints = ['\u0105', '\u0104', '\u010D', '\u010C', '\u0119', '\u0118', '\u0117', '\u0116', '\u012F', '\u012E', '\u0161', '\u0160', '\u0173', '\u0172', '\u016B', '\u016A', '\u017E', '\u017D'];
    $letters = ['ą', 'Ą', 'č', 'Č', 'ę', 'Ę', 'ė', 'Ė', 'į', 'Į', 'š', 'Š', 'ų', 'Ų', 'ū', 'Ū', 'ž', 'Ž'];
    $crawl = str_replace($codepoints, $letters, $crawl);
    file_put_contents($file_path, $crawl);

    if (file_exists($file_path)) {
        $get_file = file_get_contents($file_path);
        $response = new Response($get_file, 200);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    return $app->json([
        'success' => 0,
        'message' => "Unable to fetch data.",
    ], 503);
});
