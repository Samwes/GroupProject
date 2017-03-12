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

//TODO: learn and use extend or this wont work (I think you can only assign to controllers once, then its an extend)

//TODO: The javascript files we have are the fulll webkits. Scrub out what we need only
//note maybe change logging at heroku level, dont care about most (successful) connections

// -------- SERVICES --------
//note moved to class
// ----------------------------

// -------- SECURITY --------
//note moved to class
// ----------------------------

// -------- REST API --------
//note moved to class
// ----------------------------

// -------- WEB PAGES --------
//note moved to class

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
