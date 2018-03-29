<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class InputHandlerTest extends \PHPUnit\Framework\TestCase
{

    public function testGet()
    {
        $this->assertEquals(true, true);
    }

    public function testPost()
    {
        $this->assertEquals(true, true);
    }

    public function testFile()
    {
        $this->assertEquals(true, true);
    }

    public function testFiles()
    {
        $this->assertEquals(true, true);
    }

    public function testAll()
    {
        $this->assertEquals(true, true);
    }

}