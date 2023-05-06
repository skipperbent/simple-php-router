<?php

require_once 'Dummy/Route/DummyLoadableRoute.php';

class LoadableRouteTest extends \PHPUnit\Framework\TestCase
{
    public function testSetUrlUpdatesParameters()
    {
        $route = new DummyLoadableRoute();
        $this->assertEmpty($route->getParameters());

        $route->setUrl('/');
        $this->assertEmpty($route->getParameters());

        $expected = ['param' => null, 'optionalParam' => null];
        $route->setUrl('/{param}/{optionalParam?}');
        $this->assertEquals($expected, $route->getParameters());

        $expected = ['otherParam' => null];
        $route->setUrl('/{otherParam}');
        $this->assertEquals($expected, $route->getParameters());

        $expected = [];
        $route->setUrl('/');
        $this->assertEquals($expected, $route->getParameters());
    }
}