<?php

class CustomClassLoader implements \Pecee\SimpleRouter\ClassLoader\IClassLoader
{
    public function loadClass(string $class)
    {
        return new DummyController();
    }

    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return object
     */
    public function loadClassMethod($class, string $method, array $parameters): object
    {
        $result = call_user_func_array([$class, $method], array_values($parameters));

        return is_object($result) ? $result : new \stdClass();
    }

    public function loadClosure(callable $closure, array $parameters)
    {
        return call_user_func_array($closure, [true]);
    }
}