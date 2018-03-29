<?php

namespace Pecee\SimpleRouter\ClassLoader;

use DI\Container;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class ClassLoader implements IClassLoader
{
    /**
     * Dependency injection enabled
     * @var bool
     */
    protected $useDependencyInjection = false;

    /**
     * @var Container|null
     */
    protected $container;

    /**
     * Load class
     *
     * @param string $class
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function loadClass(string $class)
    {
        if (class_exists($class) === false) {
            throw new NotFoundHttpException(sprintf('Class "%s" does not exist', $class), 404);
        }

        if ($this->useDependencyInjection === true) {
            $container = $this->getContainer();
            if ($container !== null) {
                try {
                    return $container->get($class);
                } catch (\Exception $e) {
                    throw new NotFoundHttpException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
                }
            }
        }

        return new $class();
    }

    /**
     * Load closure
     *
     * @param \Closure $closure
     * @param array $parameters
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function loadClosure(\Closure $closure, array $parameters)
    {
        if ($this->useDependencyInjection === true) {
            $container = $this->getContainer();
            if ($container !== null) {
                try {
                    return $container->call($closure, $parameters);
                } catch (\Exception $e) {
                    throw new NotFoundHttpException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
                }
            }
        }

        return \call_user_func_array($closure, $parameters);
    }

    /**
     * Get dependency injector container.
     *
     * @return Container|null
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * Set the dependency-injector container.
     *
     * @param Container $container
     * @return ClassLoader
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Enable or disable dependency injection.
     *
     * @param bool $enabled
     * @return static
     */
    public function useDependencyInjection(bool $enabled): self
    {
        $this->useDependencyInjection = $enabled;

        return $this;
    }

    /**
     * Return true if dependency injection is enabled.
     *
     * @return bool
     */
    public function isDependencyInjectionEnabled(): bool
    {
        return $this->useDependencyInjection;
    }

}