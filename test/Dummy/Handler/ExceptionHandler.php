<?php
class ExceptionHandler implements \Pecee\Handler\IExceptionHandler {

    public function handleError(\Pecee\Http\Request $request, \Pecee\SimpleRouter\RouterEntry $router = null, \Exception $error){
        throw $error;
    }

}