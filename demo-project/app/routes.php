<?php
/**
 * This file contains all the routes for the project
 */

use Demo\Router;

Router::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());

Router::group(['exceptionHandler' => 'Demo\Handlers\CustomExceptionHandler'], function() {

    Router::get('/', 'DefaultController@index')->setAlias('home');
    Router::get('/contact', 'DefaultController@contact')->setAlias('contact');
    Router::get('/404', 'DefaultController@notFound')->setAlias('404');
    Router::basic('/companies', 'DefaultController@companies')->setAlias('companies');
    Router::basic('/companies/{id}', 'DefaultController@companies')->setAlias('companies');

    // Api
    Router::group(['prefix' => '/api', 'middleware' => 'Demo\Middlewares\ApiVerification'], function() {
        Router::resource('/demo', 'ApiController');
    });

});