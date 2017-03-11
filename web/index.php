<?php

require __DIR__. '/../vendor/autoload.php';

//This took a solid 2-3 hours to fix due to heroku being cunts future maybe remove this wording
if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
    $_SERVER['HTTPS']='on';
}

//fixme have own app with useful traits
$app = new Silex\Application();
//Settings
$app['debug'] = true;
define('DEBUG',true); //future remove this, just for old code. refactor it out

//future force https and redirect otherwise
//$app['controllers']
//    ->requireHttps();


//fixme mysql server has gone away. possible persistance causing error. research cleardb and persistent mode
//future learn how symfony forms work
//future cleanup our hosted js

// -------- SERVICES --------

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
    'monolog.level' => 'Logger::NOTICE',  //note change to debug if you want messages everywhere
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' =>
    array(
        __DIR__ . '/../src/views/html/',
        __DIR__ . '/../src/views/html/components',
    )
));
// Registering service controllers
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// Register session storage for between request data store
$app->register(new Silex\Provider\SessionServiceProvider());

// Register security service
$app->register(new Silex\Provider\SecurityServiceProvider());

// Generate urls from bound names
$app->register(new Silex\Provider\RoutingServiceProvider());

//
$app->register(new Silex\Provider\HttpFragmentServiceProvider());

//TODO: 2 new services, validator and form service

// Register web profiler if in debug mode
if ($app['debug']) {
    $app->register(new Main\WebProfilerServiceProvider(), array(
        'profiler.cache_dir' => __DIR__.'/../cache/profiler',
        'profiler.mount_prefix' => '/_profiler', // this is the default
    ));
}

// Register asset rerouting
$app->register(new Silex\Provider\AssetServiceProvider(), array(
    'assets.version' => 'v1',
    'assets.version_format' => '%s?version=%s',
    'assets.named_packages' => array(
        'css' => array('version' => 'css3', 'base_path' => 'stylesheets/'),
        'images' => array('base_path' => 'images/'),
        'food' => array('base_path' => 'images/food/'),
        'javascript' => array('base_path' => 'js/'),
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

// Register the user provider for security authentication
$app['user.provider'] = function () use ($app) {
    return new \Main\UserProvider($app['DB']);
};

// ----------------------------


// -------- SECURITY --------
//future @Security
//note eveyrthing is secured 3 times, maybe overkill? can put in our end notes

$app['route_class'] = '\Main\SecureRouter';

$app['security.firewalls'] = array(
    'login' => array(
        'pattern' => '^/login',  //Match all login pages
    ),

    'loggedin' => array(
        'pattern' => '^/account',
        'form' => array('login_path' => '/login', 'check_path' => '/account'),
        'users' => $app['user.provider'],
    ),

    'admin' => array(
        'pattern' => '^/admin',
        'form' => array('login_path' => '/login', 'check_path' => '/admin'),
        'users' => $app['user.provider'],
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
    array('^/account', 'ROLE_USER', 'https'),
);

// ----------------------------



// -------- REST API --------
//note: All get/posts

$app->get('/food/{foodID}', 'rest.handler:foodItemGet')
    -> assert('foodID', '\d+');

$app->get('/food/{userID}', 'rest.handler:foodItemsGet')
    -> assert('userID', '\d+');

//future Secure post for registered users only
$app->post('/food', 'rest.handler:foodItemPost')
    -> secure('ROLE_USER');

$app->post('/register/user', 'rest.handler:registerNewUser')
    -> requireHttps();

// ----------------------------



// -------- WEB PAGES --------
//note: Web handlers

$app->get('/', function() use($app) {
  return $app['twig']->render('index.twig');
})->bind('home');

//future cleam this up (double index)
$app->get('/index', function() use($app) {
    return $app['twig']->render('index.twig');
})->bind('index');

$app->get('/account/scanner', function() use($app) {
    return $app['twig']->render('scanner.twig');
})->bind('scanner');

$app->get('/account/userprofile', function() use($app) {
    return $app['twig']->render('userProfile.twig');
})->bind('user');

//TODO: Login page that causes you to actually login
$app->get('/login', function() use($app) {
    return $app['twig']->render('login.twig');
})->bind('login')->requireHttps();

//TODO: Register a person with the DB
$app->get('/register', function() use($app) {
    return $app['twig']->render('signup.twig');
})->bind('register')->requireHttps();

//note Temp, move these to proper routes
$app->get('/itempage', function() use($app) {
    return $app['twig']->render('itemPage.twig');
});
//})->bind('item');


//note these are debug pages to test security

$app->get('/admin', function() use($app) {
    return $app['twig']->render('admin.twig');
});

//fixme return to login but say why cunt
$app->get('/failed', function() use($app) {
    return $app['twig']->render('test.twig');
})->bind('failure');

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
