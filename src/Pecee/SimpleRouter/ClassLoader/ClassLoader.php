<?php

namespace Pecee\SimpleRouter\ClassLoader;

use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class ClassLoader implements IClassLoader
{
    /**
     * Load class
     *
     * @param string $class
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function loadClass(string $class)
    {
        if (\class_exists($class) === false) {
            throw new NotFoundHttpException(sprintf('Class "%s" does not exist', $class), 404);
        }

        return new $class();
    }

    /**
     * Load closure
     *
     * @param Callable $closure
     * @param array $parameters
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function loadClosure(Callable $closure, array $parameters)
    {
        return \call_user_func_array($closure, $parameters);
    }

}
