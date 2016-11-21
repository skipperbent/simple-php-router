<?php
namespace Pecee\SimpleRouter\Route;

interface IControllerRoute extends IRoute
{
	public function getController();

	public function setController($controller);

	public function getMethod();

	public function setMethod($method);
}