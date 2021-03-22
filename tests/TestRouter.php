<?php

class TestRouter extends \Pecee\SimpleRouter\SimpleRouter
{

    public function __construct()
    {
        static::request()->setHost('testhost.com');
    }

    public static function debugNoReset($testUrl, $testMethod = 'get')
    {
        $request = static::request();

        $request->setUrl((new \Pecee\Http\Url($testUrl))->setHost('local.unitTest'));
        $request->setMethod($testMethod);

        static::start();
    }

    public static function debug($testUrl, $testMethod = 'get', bool $reset = true)
    {
        try {
            static::debugNoReset($testUrl, $testMethod);
        } catch(\Exception $e) {
            static::router()->reset();
            throw $e;
        }

        if($reset === true) {
            static::router()->reset();
        }

    }

    public static function debugOutput($testUrl, $testMethod = 'get', bool $reset = true)
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