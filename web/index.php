<?php

define('DEBUG',true);
define('ROOT',__DIR__);

require ROOT . '/../vendor/autoload.php';

//This took a solid 2-3 hours to fix due to heroku being cunts future maybe remove this wording
if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
    $_SERVER['HTTPS']='on';
}

$app = new Main\App(array ( 'debug' => DEBUG));

// -------- SERVICES --------
//note moved to class (app.php)
// ----------------------------

// -------- SECURITY --------
//note moved to class (app.php)
// ----------------------------

// -------- REST API --------
//note moved to class (app.php)
// ----------------------------

// -------- WEB PAGES --------
//note moved to class (app.php)

//note these are debug pages to test security, remove asap or change into something usabel

$app->get('/admin', function() use($app) {
    return $app['twig']->render('admin.twig');
});

//fixme return to login but say why cunt or something
$app->get('/failed', function() use($app) {
    return $app['twig']->render('failure.twig');
})->bind('failure');
// ----------------------------


// -------- ERROR HANDLING --------
//note moved to class
// ----------------------------

//Finally Run

$app->run();
