<?php

namespace Pecee\Support;

use Closure;
use Pecee\SimpleRouter\SimpleRouter as Router;
use Pecee\Http\Url;
use Pecee\Http\Response;
use Pecee\Http\Request;
use Pecee\Support\Arr;


class Helpers
{
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function value(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    public function data_get($target, $key, $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if (!is_iterable($target)) {
                    return $this->value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = $this->data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $this->value($default);
            }
        }

        return $target;
    }
}
// /**
//  * Get url for a route by using either name/alias, class or method name.
//  *
//  * The name parameter supports the following values:
//  * - Route name
//  * - Controller/resource name (with or without method)
//  * - Controller class name
//  *
//  * When searching for controller/resource by name, you can use this syntax "route.name@method".
//  * You can also use the same syntax when searching for a specific controller-class "MyController@home".
//  * If no arguments is specified, it will return the url for the current loaded route.
//  *
//  * @param string|null $name
//  * @param string|array|null $parameters
//  * @param array|null $getParams
//  * @return \Pecee\Http\Url
//  * @throws \InvalidArgumentException
//  */
// function url(?string $name = null, $parameters = null, ?array $getParams = null): Url
// {
//     return Router::getUrl($name, $parameters, $getParams);
// }

// /**
//  * @return \Pecee\Http\Response
//  */
// function response(): Response
// {
//     return Router::response();
// }

// /**
//  * @return \Pecee\Http\Request
//  */
// function request(): Request
// {
//     return Router::request();
// }

// /**
//  * Get input class
//  * @param string|null $index Parameter index name
//  * @param string|mixed|null $defaultValue Default return value
//  * @param array ...$methods Default methods
//  * @return \Pecee\Http\Input\InputHandler|array|string|null
//  */
// function input($index = null, $defaultValue = null, ...$methods)
// {
//     if ($index !== null) {
//         return request()->getInputHandler()->value($index, $defaultValue, ...$methods);
//     }

//     return request()->getInputHandler();
// }

// /**
//  * @param string $url
//  * @param int|null $code
//  */
// function redirect(string $url, ?int $code = null): void
// {
//     if ($code !== null) {
//         response()->httpCode($code);
//     }

//     response()->redirect($url);
// }

// /**
//  * Get current csrf-token
//  * @return string|null
//  */
// function csrf_token(): ?string
// {
//     $baseVerifier = Router::router()->getCsrfVerifier();
//     if ($baseVerifier !== null) {
//         return $baseVerifier->getTokenProvider()->getToken();
//     }

//     return null;
// }
