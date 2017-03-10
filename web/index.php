<?php

//future learn symfony forms and have them do shit
//future have reorouter twig service that loads things from src/ rather than web/ where people can access it

use Main\SecureRouter;

require __DIR__. '/../vendor/autoload.php';

$app = new Silex\Application();
//Settings
$app['debug'] = true;

//TODO: Maybe use assetic instead
//TODO: twig asset command to hide template elemenets away from there
//TODO: Move twig assets etc. to source, only have web with stuff that needs exposing
//TODO: resize function changes yo. have default and then override cos BAMF
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

// Register view rendering  // future note can change this instead?
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views/html/',
));
// Registering service controllers
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// Register session storage for between request data store
$app->register(new Silex\Provider\SessionServiceProvider());

// Register security service
$app->register(new Silex\Provider\SecurityServiceProvider());

// Register asset rerouting through twig
$app->register(new Silex\Provider\AssetServiceProvider(), array(
    'assets.version' => 'v1',
    'assets.version_format' => '%s?version=%s',
    'assets.named_packages' => array(
        'css' => array('version' => 'css3', 'base_path' => 'stylesheets/'),
        'images' => array('base_path' => 'images/'),
        'food' => array('base_path' => 'images/food/'),
        'javascript' => array('base_path' => 'js/'),
        'twigcomp' => array('base_path' => '/../src/views/components/'),
        'html' => array('base_path' => '/../src/views/html/'), // note redundant
    ),
));

// Register DB provider service
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

$app['route_class'] = SecureRouter::class;

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
  return $app['twig']->render('index.twig');
})->bind('home');

//future cleam this up (double index)
$app->get('/index', function() use($app) {
    return $app['twig']->render('index.twig');
});

$app->get('/account/scanner', function() use($app) {
    return $app['twig']->render('scanner.twig');
})->bind('scanner');

$app->get('/account/userprofile', function() use($app) {
    return $app['twig']->render('userProfile.twig');
})->bind('user');


//TODO: Login page that causes you to actually login
$app->get('/login', function() use($app) {
    return $app['twig']->render('login.twig');
})->bind('login');

//TODO: Register app
$app->get('/register', function() use($app) {
    return $app['twig']->render('signup.twig');
})->bind('register');

//note Temp, move these to proper routes
$app->get('/itempage', function() use($app) {
    return $app['twig']->render('itemPage.twig');
});
//})->bind('item');


//fixme these are debug pages to test security

$app->get('/admin', function() use($app) {
    return $app['twig']->render('admin.twig');
});

$app->get('/account', function() use($app) {
    return $app['twig']->render('admin.twig');
});

//$app->get('/login', function() use($app) {
//    return $app['twig']->render('admin.twig');
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
