<?php

require __DIR__. '/../vendor/autoload.php';

$app = new Silex\Application();
//Settings
$app['debug'] = true;

if (!$app['debug']){
    $app['controllers']
        ->requireHttps(); //We can change it so only some pages require https
}

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

// Register DB service
$app['DB'] = function() {
    return new \Database\DBDataMapper(\Database::getPDO());
};

// Register our routing controllers
$app['rest.handler'] = function() use ($app) {
    return new \Handler\Controller($app['DB']);
};

//TODO: @Security


//TODO: All get/posts

$app->get('/food/{foodID}', 'rest.controller:foodItemGet')
    -> assert('foodID', '\d+');

$app->get('/food/{userID}', 'rest.controller:foodItemsGet')
    -> assert('userID', '\d+');

//TODO: Our web handlers

$app->get('/', function() use($app) {
  return $app['twig']->render('index.html.twig', array(
      'bodytags' => 'onResize=resize()'
  ));
})->bind('home');

$app->get('/scanner', function() use($app) {
    return $app['twig']->render('scanner.html.twig', array(
        'bodytags' => 'onResize=resize()'
    ));
});


// Error Handlings

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        // in debug mode we want to get the regular error message
        return;
    }
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }
    return new Response($message);
});

//Finally Run

$app->run();
