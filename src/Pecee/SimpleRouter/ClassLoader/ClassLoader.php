<?php declare(strict_types=1);

namespace Pecee\SimpleRouter\ClassLoader;

use Pecee\Container\Container;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

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
        if (\class_exists($class) === false) {
            throw new NotFoundHttpException(sprintf('Class "%s" does not exist', $class), 404);
        }

        return new $class();

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
        $result = call_user_func_array([$class, $method], array_values($parameters));

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
        return \call_user_func_array($closure, array_values($parameters));
    }

}