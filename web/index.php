<?php

require __DIR__. '../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
$app['controllers']
    ->requireHttps(); //We can change it so only some pages require https

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
// Registering service controllers
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// Register URL generator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// Create our routing controllers
//TODO: Classes!
$protected = $app['controllers_factory'];
$protected->before();


// Our web handlers

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.'); //TODO ?
  return $app['twig']->render('index.twig');
});

$app->run();
