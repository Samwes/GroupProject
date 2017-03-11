<?php

require __DIR__. '/../vendor/autoload.php';

//This took a solid 2-3 hours to fix due to heroku being cunts future maybe remove this wording
if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
    $_SERVER['HTTPS']='on';
}

//TODO: have own app with useful traits
//TODO: Start extending all their classes with our own exits.
//TODO: todo filters for each todo (fixme, future) (useful IDE thing) in alt-6 menu

$app = new Silex\Application();
//Setting
$app['debug'] = true;
define('DEBUG',true); //future remove this, just for old code. refactor it out completely

//future force https and redirect otherwise
//TODO: learn and use extend or this wont work (I think you can only assign to controllers once, then its an extend)
//note as we assign here, routing cant register later
//fixme maybe just do this after routing service proider
//$app['controllers']
//    ->requireHttps();

//TODO: The javascript files we have are the fulll webkits. Scrub out what we need.
//note maybe change logging at heroku level, dont care about most (successful) connections

// -------- SERVICES --------

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
    'monolog.level' => 'WARNING',  //note change to debug if you want messages everywhere
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
//TODO: remember me

// Generate urls from bound names
$app->register(new Silex\Provider\RoutingServiceProvider());

//note what is this
$app->register(new Silex\Provider\HttpFragmentServiceProvider());

//future: 2 new services, validator and form service? bootstrap forms?
//TODO: emailing and account validation

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
//future use converters that input username and output a user class, either security or otherwise
$app['user.provider'] = function () use ($app) {
    return new \Main\UserProvider($app['DB']);
};

// ----------------------------


// -------- SECURITY --------
//future look into the entire security package, make use of it all
//example: security.providers for database users and user class instead of users in each firewall
//note eveyrthing is secured 3 times, maybe overkill? can put in our end notes

//future logout

$app['route_class'] = '\Main\SecureRouter';

$app['security.firewalls'] = array(
    'login' => array(
        'pattern' => '^/login',  //Match all login pages
    ),
    //future seperate logins or some shit
    'loggedin' => array(
        'pattern' => '^/account',
        'form' => array('login_path' => '/login', 'check_path' => '/account/login/check'),
        'users' => $app['user.provider'],
    ),

    'admin' => array( //note no idea if this works (the login check part for admin accounts)
        'pattern' => '^/admin',
        'form' => array('login_path' => '/login', 'check_path' => '/admin/login/check'),
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

//future get rid of these, maybe even from the subclass. They fucking suck
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

// -------- WEB PAGES --------
//future Web handlers with controller as service, seperate class.
//future Move this all out, app class, router (get/post) classes etc. (split normal/admin/user etc?)

$app->get('/', function() use($app) {
    return $app['twig']->render('index.twig');
})->bind('index');

//future cleam this up (double index)
$app->get('/index', function() use($app) {
    return new RedirectResponse($app['url_generator']->generate('index'));
});

$app->get('/account/scanner', function() use($app) {
    return $app['twig']->render('scanner.twig');
})->bind('scanner');

$app->get('/account/userprofile', function() use($app) {
    return $app['twig']->render('userProfile.twig');
})->bind('user');

$app->get('/login', function(Request $request) use ($app) { //fixme Use app to get request or use request as well?
    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
})->bind('login');

$app->get('/register', function() use($app) {
    return $app['twig']->render('signup.twig');
})->bind('register')->requireHttps();

//note Temp, move these to proper routes. Fill them with data as well
$app->get('/itempage', function() use($app) {
    return $app['twig']->render('itemPage.twig');
});
//})->bind('item');


//note these are debug pages to test security, remove asap or change into something usabel

$app->get('/admin', function() use($app) {
    return $app['twig']->render('admin.twig');
});

//fixme return to login but say why cunt or something
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
//future handle authentication errors with redirects and messages

//note need better error handling here
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
