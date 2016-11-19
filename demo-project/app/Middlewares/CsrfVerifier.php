<?php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\BaseCsrfVerifier;

class CsrfVerifier extends BaseCsrfVerifier
{
	/**
	 * CSRF validation will be ignored on the following urls.
	 */
	protected $except = ['/api/*'];

}