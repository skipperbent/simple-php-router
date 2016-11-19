<?php
namespace Pecee\SimpleRouter;

abstract class LoadableRoute extends RouterEntry implements ILoadableRoute
{
	const PARAMETERS_REGEX_MATCH = '%s([\w\-\_]*?)\%s{0,1}%s';
	const PARAMETER_MODIFIERS = '{}';
	const PARAMETER_OPTIONAL_SYMBOL = '?';

	protected $url;
	protected $alias;

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
	 * Get alias for the url which can be used when getting the url route.
	 * @return string|array
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Check if route has given alias.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasAlias($name)
	{
		if ($this->getAlias() !== null) {
			if (is_array($this->getAlias()) === true) {
				foreach ($this->getAlias() as $alias) {
					if (strtolower($alias) === strtolower($name)) {
						return true;
					}
				}
			}
			return strtolower($this->getAlias()) === strtolower($name);
		}

		return false;
	}

	/**
	 * Set the url alias for easier getting the url route.
	 * @param string|array $alias
	 * @return static
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;
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
		// Change as to alias
		if (isset($values['as'])) {
			$this->setAlias($values['as']);
		}

		parent::merge($values);
		return $this;
	}

}