<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

interface IRoute
{
	public function getIdentifier();

	public function setRequestMethods(array $methods);

	public function getRequestMethods();

	/**
	 * @return IRoute
	 */
	public function getParent();

	/**
	 * @return IGroupRoute
	 */
	public function getGroup();

	public function setGroup(IGroupRoute $group);

	public function setParent(IRoute $parent);

	public function setCallback($callback);

	public function getCallback();

	public function getMethod();

	public function getClass();

	public function setMethod($method);

	public function setNamespace($namespace);

	public function setDefaultNamespace($namespace);

	public function getDefaultNamespace();

	public function getNamespace();

	public function toArray();

	public function setSettings(array $settings, $merge = false);

	public function matchRoute(Request $request);

	public function setMatch($regex);

	public function setWhere(array $options);

	public function getWhere();

	public function getParameters();

	public function setParameters(array $parameters);

	public function renderRoute(Request $request);

}