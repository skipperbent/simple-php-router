<?php

/**
 * Custom router which handles default middlewares, default exceptions and things
 * that should be happen before and after the router is initialised.
 */

namespace Demo;

use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    public static function start($defaultNamespace = null) {

        // Load our helpers
        require_once 'helpers.php';

        // Load our custom routes
        require_once 'routes.php';

        // Do initial stuff
        parent::start('\\Demo\\Controllers');

    }

}