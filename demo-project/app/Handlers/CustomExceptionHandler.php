<?php
namespace Demo\Handlers;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\RouterEntry;

class CustomExceptionHandler implements IExceptionHandler
{
	public function handleError(Request $request, RouterEntry &$route = null, \Exception $error)
	{

		/* You can use the exception handler to format errors depending on the request and type. */

		if (stripos($request->getUri(), '/api') !== false) {

			response()->json([
				'error' => $error->getMessage(),
				'code'  => $error->getCode(),
			]);

		}

		/* The router will throw the NotFoundHttpException on 404 */
		if($error instanceof NotFoundHttpException) {

			/*
			 * Render your own custom 404-view, rewrite the request to another route,
			 * or simply return the $request object to ignore the error and continue on rendering the route.
			 *
			 * The code below will make the router render our page.notfound route.
			 */

			$request->setUri(url('page.notfound'));
			return $request;

		}

		throw $error;

	}

}