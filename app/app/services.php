<?php
$app = new Silex\Application();
$app['debug'] = true;

$loader = [
    'root' => __DIR__ . '/../../',
    'config' => __DIR__ . '/../config/',
    'view' =>  __DIR__ . '/../views/',
    'thumb' => __DIR__ . '/../../thumb/'
];
$app['loader'] = $loader;

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => $loader["view"]
]);

$app['ini'] = parse_ini_file($loader["config"] . 'app.ini', true);

$app["upload"] = $loader["root"] . $app['ini']['main']['upload'];

return $app;
