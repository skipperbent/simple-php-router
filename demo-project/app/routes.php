<?php
/**
 * This file contains all the routes for the project
 */

use Demo\Router;

Router::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());

Router::group(['exceptionHandler' => 'Demo\Handlers\CustomExceptionHandler'], function () {

	Router::get('/', 'DefaultController@index')->setName('home');

	Router::get('/contact', 'DefaultController@contact')->setName('contact');

	Router::get('/404', 'DefaultController@notFound')->setName('404');

	Router::basic('/companies/{id?}', 'DefaultController@companies')->setName('companies');

	// Api
	Router::group(['prefix' => '/api', 'middleware' => 'Demo\Middlewares\ApiVerification'], function () {
		Router::resource('/demo', 'ApiController');
	});

});