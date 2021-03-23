<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/ClassLoader/CustomClassLoader.php';

class ClassLoaderTest extends \PHPUnit\Framework\TestCase
{

    public function testCustomClassLoader()
    {
        $result = false;

        TestRouter::setCustomClassLoader(new CustomClassLoader());

        TestRouter::get('/', 'NonExistingClass@method3');
        TestRouter::get('/test-closure', function($status) use(&$result) {
            $result = $status;
        });

        $classLoaderClass = TestRouter::debugOutput('/', 'get', false);
        TestRouter::debugOutput('/test-closure');

        $this->assertEquals('method3', $classLoaderClass);
        $this->assertTrue($result);

        TestRouter::router()->reset();
    }

}