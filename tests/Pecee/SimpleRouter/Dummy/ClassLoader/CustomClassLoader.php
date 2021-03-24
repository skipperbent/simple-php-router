<?php

class CustomClassLoader implements \Pecee\SimpleRouter\ClassLoader\IClassLoader
{
    public function loadClass(string $class)
    {
        return new DummyController();
    }

    public function loadClosure(callable $closure, array $parameters)
    {
        return \call_user_func_array($closure, ['status' => true]);
    }
}