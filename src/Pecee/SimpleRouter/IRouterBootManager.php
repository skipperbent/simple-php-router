<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

interface IRouterBootManager
{
	public function boot(Request $request);
}