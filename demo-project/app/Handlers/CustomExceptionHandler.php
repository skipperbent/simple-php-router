<?php
namespace Demo\Handlers;

use Pecee\Handler\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

class CustomExceptionHandler implements IExceptionHandler {

    public function handleError( Request $request, RouterEntry $router = null, \Exception $error) {

        // Return json errors if we encounter an error on /api.
        if(stripos($request->getUri(), '/api') !== false) {
            header('content-type: application/json');
            echo json_encode([
                'error' => $error->getMessage(),
                'code' => $error->getCode()
            ]);
            die();
        }

        // else we just throw the error
        if($error->getCode() == 404) {

            // Return 404 path
            $request->setUri('/404');
            return $request;

        }

        throw $error;
    }

}