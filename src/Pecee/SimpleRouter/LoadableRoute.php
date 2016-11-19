<?php
namespace Pecee\SimpleRouter;

abstract class LoadableRoute extends RouterEntry implements ILoadableRoute
{
	const PARAMETERS_REGEX_MATCH = '%s([\w\-\_]*?)\%s{0,1}%s';
	const PARAMETER_MODIFIERS = '{}';
	const PARAMETER_OPTIONAL_SYMBOL = '?';

	protected $url;
	protected $names = array();

	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Set url
	 *
	 * @param string $url
	 * @return static
	 */
	public function setUrl($url)
	{
		$this->url = ($url === '/') ? '/' : '/' . trim($url, '/') . '/';
		$regex = sprintf(static::PARAMETERS_REGEX_MATCH, static::PARAMETER_MODIFIERS[0], static::PARAMETER_OPTIONAL_SYMBOL, static::PARAMETER_MODIFIERS[1]);

		if (preg_match_all('/' . $regex . '/is', $this->url, $matches)) {
			foreach ($matches[1] as $key) {
				$this->parameters[$key] = null;
			}
		}

		return $this;
	}

	/**
	 * Returns the provided name of the router (first if multiple).
	 * Alias for LoadableRoute::getName().
	 *
	 * @see LoadableRoute::getName()
	 * @return string|array
	 */
	public function getAlias()
	{
		return $this->getName();
	}

	/**
	 * Returns the provided name for the router (first if multiple).
	 * @return string
	 */
	public function getName()
	{
		return $this->names[0];
	}

	/**
	 * Get route names
	 * @return array
	 */
	public function getNames() {
		return $this->names;
	}

	/**
	 * Check if route has given name.
	 * Alias for LoadableRoute::hasName();
	 *
	 * @see LoadableRoute::hasName()
	 * @param $name
	 */
	public function hasAlias($name)
	{
		$this->hasName($name);
	}

	/**
	 * Check if route has given name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasName($name)
	{
		return (in_array($name, $this->names, false) !== false);
	}

	/**
	 * Sets the router name, which makes it easier to obtain the url or router at a later point.
	 * Alias for LoadableRoute::setName().
	 *
	 * @see LoadableRoute::setName()
	 * @param string|array $name
	 * @return static
	 */
	public function setAlias($name)
	{
		return $this->setName($name);
	}

	/**
	 * Sets the router name, which makes it easier to obtain the url or router at a later point.
	 *
	 * @param string $name
	 * @return static $this
	 */
	public function setName($name)
	{
		array_push($this->names, $name);
		return $this;
	}

	/**
	 * Set multiple names for the route
	 *
	 * @param array $names
	 * @return static $this
	 */
	public function setNames(array $names) {
		$this->names = $names;
		return $this;
	}

	/**
	 * Merge with information from another route.
	 *
	 * @param array $values
	 * @return static
	 */
	public function merge(array $values)
	{
		if (isset($values['as'])) {
			$this->setNames((array)$values['as']);
		}

		if (isset($values['prefix'])) {
			$this->setUrl($values['prefix'] . $this->getUrl());
		}

		parent::merge($values);

		return $this;
	}

}