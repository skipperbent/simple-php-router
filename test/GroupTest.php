<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';

class GroupTest extends PHPUnit_Framework_TestCase  {

    protected $result;

    public function testGroupLoad() {

        $this->result = false;

        \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/group'], function() {
            $this->result = true;
        });

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        } catch(Exception $e) {
            echo $e->getMessage();
        }

        $this->assertTrue($this->result);
    }

    public function testNestedGroup() {

        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/api/v1/test');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');

        \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/api'], function() {
            \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/v1'], function() {
                \Pecee\SimpleRouter\SimpleRouter::get('/test', 'DummyController@start');
            });
        });

        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testManyRoutes() {

        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/my/match');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');

        \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/api'], function() {
            \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/v1'], function() {
                \Pecee\SimpleRouter\SimpleRouter::get('/test', 'DummyController@start');
            });
        });

        \Pecee\SimpleRouter\SimpleRouter::get('/my/match', 'DummyController@start');

        \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/service'], function() {
            \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/v1'], function() {
                \Pecee\SimpleRouter\SimpleRouter::get('/no-match', 'DummyController@start');
            });
        });

        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testUrls() {

        \Pecee\SimpleRouter\SimpleRouter::get('/my/fancy/url/1', 'DummyController@start', ['as' => 'fancy1']);
        \Pecee\SimpleRouter\SimpleRouter::get('/my/fancy/url/2', 'DummyController@start')->setAlias('fancy2');

        \Pecee\SimpleRouter\SimpleRouter::start();

        $this->assertTrue((\Pecee\SimpleRouter\SimpleRouter::getRoute('fancy1') === '/my/fancy/url/1/'));
        $this->assertTrue((\Pecee\SimpleRouter\SimpleRouter::getRoute('fancy2') === '/my/fancy/url/2/'));

    }

}