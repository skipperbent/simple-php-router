<?php
namespace Pecee\SimpleRouter;

interface IControllerRoute
{
	public function getController();

	public function setController($controller);

	public function getMethod();

	public function setMethod($method);
}