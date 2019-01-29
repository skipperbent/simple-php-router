<?php

namespace Pecee\SimpleRouter\ClassLoader;

/**
 * Interface IClassLoader
 *
 * @package Pecee\SimpleRouter\ClassLoader
 */
interface IClassLoader
{
    /**
     * @param string $class
     * @return mixed
     */
    public function loadClass(string $class);

    /**
     * @param \Closure $closure
     * @param array $parameters
     * @return mixed
     */
    public function loadClosure(\Closure $closure, array $parameters);
}