<?php

require __DIR__. '/../vendor/autoload.php';

//This took a solid 2-3 hours to fix due to heroku being cunts future maybe remove this wording
if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
    $_SERVER['HTTPS']='on';
}

//TODO: Start extending all their classes with our own exits.
//TODO: todo filters for each todo (fixme, future) (useful IDE thing) in alt-6 menu

$app = new Main\App();
//Setting
$app['debug'] = true;
define('DEBUG',true); //future remove this, just for old code. refactor it out completely

//future force https and redirect otherwise
//TODO: learn and use extend or this wont work (I think you can only assign to controllers once, then its an extend)
//note as we assign here, routing cant register later
//fixme maybe just do this after routing service proider
//$app['controllers']
//    ->requireHttps();

//TODO: The javascript files we have are the fulll webkits. Scrub out what we need only
//note maybe change logging at heroku level, dont care about most (successful) connections

// -------- SERVICES --------
//note moved to class
// ----------------------------


// -------- SECURITY --------
//note moved to class
// ----------------------------


//future decide where these will go. Own classes, app class, new controllers. whatever. todo

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
