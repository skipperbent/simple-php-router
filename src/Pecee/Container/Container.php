<?php

namespace Pecee\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Container
{
    public function resolve(string $className)
    {
        $reflectionClass = new ReflectionClass($className);

        $construct = $reflectionClass->getConstructor();

        if (!$construct) {
            return new $className;
        }

        $parameters = $construct->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {

            $dependencyClass = $parameter->getType();


            if ($dependencyClass) {
                $dependencies[] = $this->resolve($dependencyClass->getName());
            } else {
                // Se você não sabe o tipo da dependência, pode lidar aqui de acordo com suas necessidades.
                // Por exemplo, você pode lançar uma exceção ou fornecer um valor padrão.
            }
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    public function resolveMethod($instance, $method, array $routeParams)
    {
        $reflectionMethod = new ReflectionMethod($instance, $method);
        $parameters = $reflectionMethod->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencyClass = $parameter->getType();
            $parameterName = $parameter->getName();

            if ($dependencyClass) {
                $dependencies[] = $this->resolve($dependencyClass->getName());
            } elseif (array_key_exists($parameterName, $routeParams)) {
                $dependencies[] = $routeParams[$parameterName];
            } else {
                // Lidar com valores não resolvidos ou lançar exceção caso necessário.
            }
        }

        return call_user_func_array([$instance, $method], $dependencies);
    }

    public function resolveClousure(callable $closure, array $routeParams)
    {
        $reflectionClosure = new ReflectionFunction($closure);
        $parameters = $reflectionClosure->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencyClass = $parameter->getType();
            $parameterName = $parameter->getName();

            if ($dependencyClass) {
                $dependencies[] = $this->resolve($dependencyClass->getName());
            } elseif (array_key_exists($parameterName, $routeParams)) {
                $dependencies[] = $routeParams[$parameterName];
            } else {
                // Lidar com valores não resolvidos ou lançar exceção caso necessário.
            }
        }

        return call_user_func_array($closure, $dependencies);
    }
}