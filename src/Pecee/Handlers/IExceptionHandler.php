<?php
namespace Pecee\Handlers;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

interface IExceptionHandler
{
    /**
     * @param Request $request
     * @param ILoadableRoute $route
     * @param \Exception $error
     * @return Request|null
     */
    public function handleError(Request $request, ILoadableRoute &$route = null, \Exception $error);

}