<?php declare(strict_types=1);

namespace Pecee\SimpleRouter\ClassLoader;

use Pecee\Container\Container;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException;

class ClassLoader implements IClassLoader
{
    /**
     * Load class
     *
     * @param string $class
     * @return object
     * @throws ClassNotFoundHttpException
     */
    public function loadClass(string $class)
    {
        if (class_exists($class) === false) {
            throw new ClassNotFoundHttpException($class, null, sprintf('Class "%s" does not exist', $class), 404, null);
        }

        return (new Container)->resolve($class);

    }

    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return string
     */
    public function loadClassMethod($class, string $method, array $parameters): object
    {
        $result = (new Container)->resolveMethod($class, $method, $parameters);

        return is_object($result) ? $result : new \stdClass();
    }

    /**
     * Load closure
     *
     * @param callable $closure
     * @param array $parameters
     * @return string
     */
    public function loadClosure(callable $closure, array $parameters)
    {
        return (new Container)->resolveClousure($closure, $parameters);
    }

}