<?php

//future learn symfony forms and have them do shit
//future have reorouter twig service that loads things from src/ rather than web/ where people can access it

require __DIR__. '/../vendor/autoload.php';

$app = new Silex\Application();
//Settings
$app['debug'] = true;

//TODO: twig asset command to hide template elemenets away from there
//TODO: Move twig assets etc. to source, only have web with stuff that needs exposing
//future HTTPs only important pages
if (!$app['debug']){
    $app['controllers']
        ->requireHttps(); //We can change it so only some pages require https
}

// -------- SERVICES --------

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

// Register session storage for between request data store
$app->register(new Silex\Provider\SessionServiceProvider());

// Register security service
$app->register(new Silex\Provider\SecurityServiceProvider());

// Register DB service
$app['DB'] = function() {
    return new \Database\DBDataMapper();
};

// Register our routing controllers
$app['rest.handler'] = function() use ($app) {
    return new \Handler\Controller($app['DB']);
};
// ----------------------------


// -------- SECURITY --------
//fixme @Security

$app['route_class'] = 'Main\SecureRoute';

$app['security.firewalls'] = array(
    'login' => array(
        'pattern' => '^/login',  //Match all login pages
    ),

    'secure' => array(
        'pattern' => '^/account',  //Doesn't match admin but handled below (?)
        'form' => array('login_path' => '/login', 'check_path' => '/account'),
        'users' => function () use ($app) {
            return new \Main\UserProvider($app['DB']);
        },
    ),

    'unsecured' => array(
        'anonymous' => true,
        'switch_user' => array('parameter' => '_switch_user', 'role' => 'ROLE_ALLOWED_TO_SWITCH'),
    ),
);

$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
);

$app['security.access_rules'] = array(
    array('^/admin', 'ROLE_ADMIN', 'https'),
    array('^/account', 'ROLE_USER'),
);

// ----------------------------



// -------- REST API --------
//note: All get/posts

$app->get('/food/{foodID}', 'rest.controller:foodItemGet')
    -> assert('foodID', '\d+');

$app->get('/food/{userID}', 'rest.controller:foodItemsGet')
    -> assert('userID', '\d+');

//future Secure post for registered users only
$app->post('/food', 'rest.controller:foodItemPost')
    -> secure('ROLE_USER');


// ----------------------------



// -------- WEB PAGES --------
//note: Web handlers

$app->get('/', function() use($app) {
  return $app['twig']->render('index.html.twig', array(
      'bodytags' => 'onResize=resize()'
  ));
})->bind('home');

//future cleam this up (double index)
$app->get('/index', function() use($app) {
    return $app['twig']->render('index.html.twig', array(
        'bodytags' => 'onResize=resize()'
    ));
});

$app->get('/scanner', function() use($app) {
    return $app['twig']->render('scanner.html.twig');
})->bind('scanner');

$app->get('/account/userprofile', function() use($app) {
    return $app['twig']->render('userProfile.html.twig', array(
        'bodytags' => 'onResize=resize()'
    ));
})->bind('user');


//TODO: Login page causes you to login
$app->get('/login', function() use($app) {
    return $app['twig']->render('login.html.twig', array(
        'bodytags' => 'onResize=resize()'
    ));
})->bind('login');

$app->get('/register', function() use($app) {
    return $app['twig']->render('signup.html.twig', array(
        'bodytags' => 'onResize=resize()'
    ));
})->bind('register');

//note Temp, move these to proper routes
$app->get('/itempage', function() use($app) {
    return $app['twig']->render('itemPage.html.twig', array(
        'bodytags' => 'onResize=resize()'
    ));
});
//})->bind('item');


//fixme these are debug pages to test security

$app->get('/admin', function() use($app) {
    return $app['twig']->render('admin.html.twig');
});

$app->get('/account', function() use($app) {
    return $app['twig']->render('admin.html.twig');
});

//$app->get('/login', function() use($app) {
//    return $app['twig']->render('admin.html.twig');
//});



// ----------------------------


// -------- ERROR HANDLING --------
//future better error handling here
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

// ----------------------------

//Finally Run

$app->run();
