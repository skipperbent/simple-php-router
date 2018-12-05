<?php

namespace Pecee\SimpleRouter\ClassLoader;

use DI\Container;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

/**
 * Class ClassLoader
 *
 * @package Pecee\SimpleRouter\ClassLoader
 */
class ClassLoader implements IClassLoader
{
    /**
     * @var bool
     */
    protected $useDependencyInjection = false;
    protected $container;

    /**
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
     * @return Container|null
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     * @return ClassLoader
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return ClassLoader
     */
    public function useDependencyInjection(bool $enabled): self
    {
        $this->useDependencyInjection = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDependencyInjectionEnabled(): bool
    {
        return $this->useDependencyInjection;
    }
}