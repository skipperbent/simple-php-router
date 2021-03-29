<?php

namespace Pecee\SimpleRouter\ClassLoader;

interface IClassLoader
{

    /**
     * Called when loading class
     * @param string $class
     * @return object
     */
    public function loadClass(string $class);

    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return object
     */
    public function loadClassMethod($class, string $method, array $parameters);

    /**
     * Called when loading method
     *
     * @param callable $closure
     * @param array $parameters
     * @return mixed
     */
    public function loadClosure(Callable $closure, array $parameters);

}