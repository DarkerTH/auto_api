<?php
/**
 * Created by PhpStorm.
 * User: darker
 * Date: 17.5.8
 * Time: 13.27
 */

namespace Services;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class API implements ServiceProviderInterface
{

    public function register(Container $app)
    {

    }

    public function crawl($crawler_path, $crawler_name, $arguments='')
    {
        $crawl = trim(shell_exec("sudo /usr/bin/python2.7 " . $crawler_path . "/".$crawler_name.".py ".$arguments));
        $translated = $this->translate($crawl);
        return $translated;
    }

    public function save($file_path, $input){
        return file_put_contents($file_path, $input);
    }

    public function response($file_path){
        $get_file = file_get_contents($file_path);
        $response = new Response($get_file, 200);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    private function translate($input){
        $codepoints = ['\u0105', '\u0104', '\u010D', '\u010C', '\u0119', '\u0118', '\u0117', '\u0116', '\u012F', '\u012E', '\u0161', '\u0160', '\u0173', '\u0172', '\u016B', '\u016A', '\u017E', '\u017D'];
        $letters = ['ą', 'Ą', 'č', 'Č', 'ę', 'Ę', 'ė', 'Ė', 'į', 'Į', 'š', 'Š', 'ų', 'Ų', 'ū', 'Ū', 'ž', 'Ž'];
        $output = str_replace($codepoints, $letters, $input);

        return $output;
    }

    public function formatModelManufacturerJSON($selectedBrand, $selectedModel, $cars_path){

        $brands_path = $cars_path . '/' . md5('brands') . '.json';
        $models_path = $cars_path . '/' . md5('models') . '.json';

        if (!file_exists($brands_path) || !file_exists($models_path)){
            return ['error', 'Brands and/or models not yet crawled.'];
        }

        $brands = json_decode(file_get_contents($brands_path));
        $models = json_decode(file_get_contents($models_path));

        if (count($brands) === 0 || count($models) === 0){
            return ['error', 'Brands and/or models are empty.'];
        }

        $data = [
            'autoplius' => [
                'manufacturer' => '',
                'model' => '',
            ],
            'autogidas' => [
                'manufacturer' => '',
                'model' => '',
            ],
        ];

        $foundBrand = 0;
        $foundModel = 0;

        if (!isset($models->{$selectedBrand})){

            return ['error', 'Brand or model does not exist.'];
        }

        foreach ($brands as $brand){
            if ($brand->brand_id === $selectedBrand){
                $data['autoplius']['manufacturer'] = $brand->brand_id;
                $data['autogidas']['manufacturer'] = str_replace(' ', '+', $brand->brand_name);

                $foundBrand = 1;
                break;
            }
        }

        foreach ($models->{$selectedBrand} as $model){
            if ($model->model_id === $selectedModel){
                $data['autoplius']['model'] = $model->model_id;
                $data['autogidas']['model'] = str_replace(' ', '+', $model->model_name);

                $foundModel = 1;
                break;
            }
        }

        if ($foundBrand === 0 || $foundModel === 0){

            return ['error', 'Manufacturer or model does not exist.'];
        }

        return json_encode($data);

    }

    public function errorJson($message){

        return [
            'errors' => [
                [
                    'message' => $message,
                ]
            ]
        ];

    }

}
