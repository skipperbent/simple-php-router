<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterGroup extends RouterEntry
{
	protected $prefix;
	protected $domains = [];
	protected $exceptionHandlers = [];

	public function matchDomain(Request $request)
	{
		if (count($this->domains) > 0) {
			foreach ($this->domains as $domain) {

				$parameters = $this->parseParameters($domain, $request->getHost(), '.*');

				if ($parameters !== null) {
					$this->parameters = $parameters;

					return true;
				}
			}

			return false;
		}

		return true;
	}

	public function matchRoute(Request $request)
	{
		// Skip if prefix doesn't match
		if ($this->prefix !== null && stripos($request->getUri(), $this->prefix) === false) {
			return false;
		}

		return $this->matchDomain($request);
	}

	public function setExceptionHandlers(array $handlers)
	{
		$this->exceptionHandlers = $handlers;

		return $this;
	}

	public function getExceptionHandlers()
	{
		return $this->exceptionHandlers;
	}

	public function getDomains()
	{
		return $this->domains;
	}

	public function setDomains(array $domains)
	{
		$this->domains = $domains;

		return $this;
	}

	/**
	 * @param string $prefix
	 * @return static
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = '/' . trim($prefix, '/');

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Merge with information from another route.
	 *
	 * @param array $values
	 * @return static
	 */
	public function setSettings(array $values)
	{
		if (isset($values['prefix'])) {
			$this->setPrefix($values['prefix'] . $this->prefix);
		}

		if (isset($values['exceptionHandler'])) {
			$this->setExceptionHandlers((array)$values['exceptionHandler']);
		}

		if (isset($values['domain'])) {
			$this->setDomains((array)$values['domain']);
		}

		parent::setSettings($values);

		return $this;
	}

	/**
	 * Export route settings to array so they can be merged with another route.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$values = [];

		if ($this->prefix !== null) {
			$values['prefix'] = $this->getPrefix();
		}

		return array_merge($values, parent::toArray());
	}

}