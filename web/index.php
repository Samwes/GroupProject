<?php

require dirname(__DIR__) . '\vendor\autoload.php';

$app = new Silex\Application();
//Settings
$app['debug'] = true;
$app['controllers']
    ->requireHttps(); //We can change it so only some pages require https

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// TODO what is this
$app->register(new Sorien\Provider\PimpleDumpProvider());
//$app['pimpledump.output_dir'] = '/';

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
// Registering service controllers
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// Register URL generator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// Register DB service
$app['DB'] = function() {
    return new \Database\DBDataMapper(\Database\getPDO());
};

// Register our routing controllers
$app['rest.handler'] = function() use ($app) {
    return new \Handler\Controller($app['DB']);
};


// Our web handlers
//TODO: All get/posts

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.'); //TODO ?
  return $app['twig']->render('index.html.twig');
});

$app->run();
