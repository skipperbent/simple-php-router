<?php

namespace Pecee\SimpleRouter\ClassLoader;

interface IClassLoader
{

    public function loadClass(string $class);

    public function loadClosure(Callable $closure, array $parameters);

}
