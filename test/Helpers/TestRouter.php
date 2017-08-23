<?php

class TestRouter extends \Pecee\SimpleRouter\SimpleRouter
{

    public static function debugNoReset($testUri, $testMethod = 'get')
    {
        static::request()->setUri(new \Pecee\Http\Uri($testUri));
        static::request()->setMethod($testMethod);

        static::start();
    }

    public static function debug($testUri, $testMethod = 'get')
    {
        try {
            static::debugNoReset($testUri, $testMethod);
        } catch(\Exception $e) {
            static::router()->reset();
            throw $e;
        }

        static::router()->reset();

    }

    public static function debugOutput($testUri, $testMethod = 'get')
    {
        $response = null;

        // Route request
        ob_start();
        static::debug($testUri, $testMethod);
        $response = ob_get_contents();
        ob_end_clean();

        // Return response
        return $response;
    }

}