<?php

class TestRouter extends \Pecee\SimpleRouter\SimpleRouter
{

    public function __construct()
    {
        static::request()->setHost('testhost.com');
    }

    public static function debugNoReset(string $testUrl, string $testMethod = 'get'): void
    {
        $request = static::request();

        $request->setUrl((new \Pecee\Http\Url($testUrl))->setHost('local.unitTest'));
        $request->setMethod($testMethod);

        static::start();
    }

    public static function debug(string $testUrl, string $testMethod = 'get', bool $reset = true): void
    {
        try {
            static::debugNoReset($testUrl, $testMethod);
        } catch (\Exception $e) {
            static::$defaultNamespace = null;
            static::router()->reset();
            throw $e;
        }

        if ($reset === true) {
            static::$defaultNamespace = null;
            static::router()->reset();
        }

    }

    public static function debugOutput(string $testUrl, string $testMethod = 'get', bool $reset = true): string
    {
        $response = null;

        // Route request
        ob_start();
        static::debug($testUrl, $testMethod, $reset);
        $response = ob_get_clean();

        // Return response
        return $response;
    }

}