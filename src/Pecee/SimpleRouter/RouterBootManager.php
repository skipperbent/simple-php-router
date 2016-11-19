<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

abstract class RouterBootManager
{
	abstract public function boot(Request $request);
}