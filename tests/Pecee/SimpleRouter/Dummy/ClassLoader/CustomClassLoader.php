<?php

class CustomClassLoader implements \Pecee\SimpleRouter\ClassLoader\IClassLoader
{
    public function loadClass(string $class): object
    {
        return new DummyController();
    }

    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function loadClassMethod(object $class, string $method, array $parameters): mixed
    {
        return call_user_func_array([$class, $method], [true]);
    }

    /**
     * @param callable $closure
     * @param array $parameters
     * @return mixed
     */
    public function loadClosure(callable $closure, array $parameters): mixed
    {
        return call_user_func_array($closure, [true]);
    }
}