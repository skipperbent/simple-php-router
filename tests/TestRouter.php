<?php

use Pecee\Http\Exceptions\MalformedUrlException;
use Pecee\Http\Url;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;

class TestRouter extends SimpleRouter
{

    public function __construct()
    {
        static::request()->setHost('https://example.com');
    }

    public static function reset(): void
    {
        static::$router = null;
    }

    /**
     * @throws \Pecee\SimpleRouter\Exceptions\HttpException
     * @throws NotFoundHttpException
     * @throws MalformedUrlException
     */
    public static function debugNoReset(string $testUrl, string $testMethod = 'get'): void
    {
        $request = static::request();

        $request->setUrl((new Url($testUrl)));
        $request->setMethod($testMethod);

        static::start();
    }

    /**
     * @throws Exception
     */
    public static function debug(string $testUrl, string $testMethod = 'get', bool $reset = true): void
    {
        try {
            static::debugNoReset($testUrl, $testMethod);
        } catch (Exception $e) {
            static::$defaultNamespace = null;
            static::router()->reset();
            throw $e;
        }

        if ($reset === true) {
            static::$defaultNamespace = null;
            static::router()->reset();
        }

    }

    /**
     * @throws Exception
     */
    public static function debugOutput(string $testUrl, string $testMethod = 'get', bool $reset = true): string
    {
        // Route request
        ob_start();

        static::debug($testUrl, $testMethod, $reset);
        // Return response
        return ob_get_clean();
    }

    /**
     * @throws \Pecee\SimpleRouter\Exceptions\HttpException
     * @throws NotFoundHttpException
     * @throws MalformedUrlException
     */
    public static function debugOutputNoReset(string $testUrl, string $testMethod = 'get'): string
    {
        // Route request
        ob_start();

        static::debugNoReset($testUrl, $testMethod);
        // Return response
        return ob_get_clean();
    }

}