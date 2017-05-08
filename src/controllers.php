<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Services\API;

//Request::setTrustedProxies(array('127.0.0.1'));
$app['API'] = function () {
    return new API();
};

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

$app->get('/models/{brand_id}', function (Request $request, $brand_id) use ($app) {
    if ($request->query->get('key') !== $app['api.key']) {
        return $app->json($app['API']->forbidden(), 401);
    }

    $brand_id = preg_replace("/[^0-9]/", "", $brand_id);

    $cars_path = $app['api.carsPath'];
    $crawler_path = $app['api.crawlerPath'];

    if (!file_exists($cars_path)) {
        mkdir($cars_path, 0777);
    }

    $file_name_md5 = md5('models');
    $file_path = $cars_path . '/' . $file_name_md5 . '.json';


    if (file_exists($file_path)) {
        $file_modified_at = filemtime($file_path);

        if (time() - $file_modified_at < $app['api.brandsCacheTime']) {
            $get_file = file_get_contents($file_path);

            $decoded = json_decode($get_file);
            if ($brand_id !== '') {
                if (!isset($decoded->{$brand_id})) {
                    return $app->json($app['API']->notFound(), 404);
                }

                $decoded = $decoded->{$brand_id};
            }
            $encoded = json_encode($decoded);

            $response = new Response($encoded, 200);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            return $response;
        }
    }

    $crawl = $app['API']->crawl($crawler_path, 'model_spider');

    $json = json_decode($crawl);
    $newData = [];
    foreach ($json as $website => $data) {
        if (!isset($newData[$website])) {
            $newData[$website] = [];
        }

        foreach ($data as $key => $model) {
            if (!isset($newData[$website][$model->brand_id])) {
                $newData[$website][$model->brand_id] = [];
            }

            $newData[$website][$model->brand_id][] = [
                'model_id' => $model->model_id,
                'model_name' => $model->model_name,
            ];
        }
    }
    $newData = $newData['autoplius'];
    $newData = json_encode($newData);

    $app['API']->save($file_path, $newData);

    if (file_exists($file_path)) {

        $get_file = file_get_contents($file_path);

        $decoded = json_decode($get_file);

        if ($brand_id !== '') {
            if (!isset($decoded->{$brand_id})) {
                return $app->json($app['API']->notFound(), 404);
            }

            $decoded = $decoded->{$brand_id};
            $encoded = json_encode($decoded);

            $response = new Response($encoded, 200);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            return $response;
        }

        return $app['API']->response($file_path);
    }

    return $app->json([
        'success' => 0,
        'message' => "Unable to fetch data.",
    ], 503);

})->value('brand_id', '');

$app->get('/brands', function (Request $request) use ($app) {
    if ($request->query->get('key') !== $app['api.key']) {
        return $app->json($app['API']->forbidden(), 401);
    }

    $cars_path = $app['api.carsPath'];
    $crawler_path = $app['api.crawlerPath'];

    if (!file_exists($cars_path)) {
        mkdir($cars_path, 0777);
    }

    $file_name_md5 = md5('brands');
    $file_path = $cars_path . '/' . $file_name_md5 . '.json';

    if (file_exists($file_path)) {
        $file_modified_at = filemtime($file_path);

        if (time() - $file_modified_at < $app['api.brandsCacheTime']) {
            return $app['API']->response($file_path);
        }

    }

    $crawl = $app['API']->crawl($crawler_path, 'brand_spider');

    $decoded = json_decode($crawl);
    $data = json_encode($decoded->autoplius);

    $app['API']->save($file_path, $data);

    if (file_exists($file_path)) {
        return $app['API']->response($file_path);
    }

    return $app->json([
        'success' => 0,
        'message' => "Unable to fetch data.",
    ], 503);

});

$app->get('/cars/{manufacturer}/{model}', function (Request $request, $manufacturer, $model) use ($app) {

    if ($request->query->get('key') !== $app['api.key']) {
        return $app->json($app['API']->forbidden(), 401);
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

        if (time() - $file_modified_at < $app['api.adsCacheTime']) {
            return $app['API']->response($file_path);
        }

    }

    $crawlerArguments = $file_name_md5 . " " . $manufacturer . " " . $model . " " . $year_from . " " . $year_to . " " . $price_from . " " . $price_to;
    $crawl = $app['API']->crawl($crawler_path, 'auto_spider', $crawlerArguments);
    $app['API']->save($file_path, $crawl);

    if (file_exists($file_path)) {
        return $app['API']->response($file_path);
    }

    return $app->json([
        'success' => 0,
        'message' => "Unable to fetch data.",
    ], 503);
});
