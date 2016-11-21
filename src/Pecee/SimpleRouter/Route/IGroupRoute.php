<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

interface IGroupRoute extends IRoute
{
	public function matchDomain(Request $request);

	public function setExceptionHandlers(array $handlers);

	public function getExceptionHandlers();

	public function getDomains();

	public function setDomains(array $domains);

	public function setPrefix($prefix);

	public function getPrefix();
}