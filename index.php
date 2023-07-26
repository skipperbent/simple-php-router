<?php
use Pecee\Controllers\TesteController;
use Pecee\SimpleRouter\SimpleRouter;


require __DIR__ . "./vendor/autoload.php";
/**
 * The default namespace for route-callbacks, so we don't have to specify it each time.
 * Can be overwritten by using the namespace config option on your routes.
 */
SimpleRouter::get('/', [TesteController::class, 'get']);

SimpleRouter::get('/user/{id}', function ($userId) {
    return 'User with id: ' . $userId;
});
// Start the routing
SimpleRouter::start();