<?php
/**
 * This file contains all the routes for the project
 */

use Demo\Router;

Router::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());
Router::defaultExceptionHandler('\Demo\Handlers\CustomExceptionHandler');

Router::get('/', 'DefaultController@index')->setAlias('home');
Router::get('/contact', 'DefaultController@contact')->setAlias('contact');
Router::basic('/companies', 'DefaultController@companies')->setAlias('companies');
Router::basic('/companies/{id}', 'DefaultController@companies')->setAlias('companies');

// Api
Router::group(['prefix' => '/api', 'middleware' => 'Demo\Middlewares\ApiVerification'], function() {
    Router::resource('/demo', 'ApiController');
});