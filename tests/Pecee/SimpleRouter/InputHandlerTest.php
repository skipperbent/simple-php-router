<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class InputHandlerTest extends \PHPUnit\Framework\TestCase
{

    public function testPost()
    {
        global $_POST;

        $names = [
            'Lester',
            'Michael',
            'Franklin',
            'Trevor',
        ];

        $day = 'monday';

        $_POST = [
            'names' => $names,
            'day' => $day,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');

        $handler = TestRouter::request()->getInputHandler();

        $this->assertEquals($names, $handler->value('names'));
        $this->assertEquals($names, $handler->all(['names'])['names']);
        $this->assertEquals($day, $handler->value('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->find('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->post('day'));

        // Check non-existing and wrong request-type
        $this->assertEmpty($handler->all(['non-existing']));
        $this->assertNull($handler->value('non-existing'));
        $this->assertNull($handler->find('non-existing'));
        $this->assertNull($handler->value('names', null, 'get'));
        $this->assertNull($handler->find('names', 'get'));

        $objects = $handler->find('names');

        $this->assertCount(4, $objects);

        /* @var $object \Pecee\Http\Input\InputItem */
        foreach($objects as $i => $object) {
            $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $object);
            $this->assertEquals($names[$i], $object->getValue());
        }

        $_POST = [];
    }

    public function testGet()
    {
        global $_GET;

        $names = [
            'Lester',
            'Michael',
            'Franklin',
            'Trevor',
        ];

        $day = 'monday';

        $_GET = [
            'names' => $names,
            'day' => $day,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('get');

        $handler = TestRouter::request()->getInputHandler();

        $this->assertEquals($names, $handler->value('names'));
        $this->assertEquals($names, $handler->all(['names'])['names']);
        $this->assertEquals($day, $handler->value('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->find('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->get('day'));

        // Check non-existing and wrong request-type
        $this->assertEmpty($handler->all(['non-existing']));
        $this->assertNull($handler->value('non-existing'));
        $this->assertNull($handler->find('non-existing'));
        $this->assertNull($handler->value('names', null, 'post'));
        $this->assertNull($handler->find('names', 'post'));

        $objects = $handler->find('names');

        $this->assertCount(4, $objects);

        /* @var $object \Pecee\Http\Input\InputItem */
        foreach($objects as $i => $object) {
            $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $object);
            $this->assertEquals($names[$i], $object->getValue());
        }

        $_GET = [];
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