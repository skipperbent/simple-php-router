<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';

class GroupTest extends PHPUnit_Framework_TestCase  {

    protected $result;

    protected function group() {
        $this->result = true;
    }

    public function testGroup() {

        $this->result = false;

        \Pecee\SimpleRouter\SimpleRouter::group(['prefix' => '/group'], $this->group());

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        } catch(Exception $e) {

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

}