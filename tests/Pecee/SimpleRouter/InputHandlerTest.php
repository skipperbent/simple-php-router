<?php

use Pecee\Http\Input\InputFile;

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class InputHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $names = [
        'Lester',
        'Michael',
        'Franklin',
        'Trevor',
    ];

    protected $brands = [
        'Samsung',
        'Apple',
        'HP',
        'Canon',
    ];

    protected $day = 'monday';

    public function testPost()
    {
        global $_POST;

        $_POST = [
            'names' => $this->names,
            'day' => $this->day,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');

        $handler = TestRouter::request()->getInputHandler();

        $this->assertEquals($this->names, $handler->value('names'));
        $this->assertEquals($this->names, $handler->all(['names'])['names']);
        $this->assertEquals($this->day, $handler->value('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->find('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->post('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->find('day', 'post'));

        // Check non-existing and wrong request-type
        $this->assertCount(1, $handler->all(['non-existing']));
        $this->assertEmpty($handler->all(['non-existing'])['non-existing']);
        $this->assertNull($handler->value('non-existing'));
        $this->assertNull($handler->find('non-existing'));
        $this->assertNull($handler->value('names', null, 'get'));
        $this->assertNull($handler->find('names', 'get'));

        $objects = $handler->find('names');

        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $objects);
        $this->assertCount(4, $objects);

        /* @var $object \Pecee\Http\Input\InputItem */
        foreach($objects as $i => $object) {
            $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $object);
            $this->assertEquals($this->names[$i], $object->getValue());
        }

        // Reset
        $_POST = [];
    }

    public function testGet()
    {
        global $_GET;

        $_GET = [
            'names' => $this->names,
            'day' => $this->day,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('get');

        $handler = TestRouter::request()->getInputHandler();

        $this->assertEquals($this->names, $handler->value('names'));
        $this->assertEquals($this->names, $handler->all(['names'])['names']);
        $this->assertEquals($this->day, $handler->value('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->find('day'));
        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $handler->get('day'));

        // Check non-existing and wrong request-type
        $this->assertCount(1, $handler->all(['non-existing']));
        $this->assertEmpty($handler->all(['non-existing'])['non-existing']);
        $this->assertNull($handler->value('non-existing'));
        $this->assertNull($handler->find('non-existing'));
        $this->assertNull($handler->value('names', null, 'post'));
        $this->assertNull($handler->find('names', 'post'));

        $objects = $handler->find('names');

        $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $objects);
        $this->assertCount(4, $objects);

        /* @var $object \Pecee\Http\Input\InputItem */
        foreach($objects as $i => $object) {
            $this->assertInstanceOf(\Pecee\Http\Input\InputItem::class, $object);
            $this->assertEquals($this->names[$i], $object->getValue());
        }

        // Reset
        $_GET = [];
    }

    public function testFile()
    {
        global $_FILES;
        $temp_dir = sys_get_temp_dir();

        $test_file_input_name = 'test_input';
        $test_file = array(
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $temp_dir . '/phpYfWUiw',
            'error' => 0,
            'size' => 4
        );
        $test_file_content = 'test_content';

        $_FILES = array(
            $test_file_input_name => $test_file
        );

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');

        $handler = TestRouter::request()->getInputHandler();
        $file = $handler->file($test_file_input_name);
        $this->assertInstanceOf(InputFile::class, $file);
        $this->assertEquals($test_file['name'], $file->getFilename());
        $this->assertEquals($test_file['type'], $file->getType());
        $this->assertEquals($test_file['tmp_name'], $file->getTmpName());
        $this->assertEquals($test_file['error'], $file->getError());
        $this->assertEquals($test_file['size'], $file->getSize());
        $this->assertEquals(pathinfo($test_file['name'], PATHINFO_EXTENSION), $file->getExtension());

        file_put_contents($test_file['tmp_name'], $test_file_content);
        $this->assertEquals($test_file_content, $file->getContents());

        //cleanup
        unlink($test_file['tmp_name']);
    }

    public function testFiles()
    {
        // TODO: implement test-files
        $this->assertEquals(true, true);
    }

    public function testAll()
    {
        global $_POST;
        global $_GET;

        $_POST = [
            'names' => $this->names,
            'is_sad' => true,
        ];

        $_GET = [
            'brands' => $this->brands,
            'is_happy' => true,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');

        $handler = TestRouter::request()->getInputHandler();

        // GET
        $brandsFound = $handler->all(['brands', 'nothing']);

        $this->assertArrayHasKey('brands', $brandsFound);
        $this->assertArrayHasKey('nothing', $brandsFound);
        $this->assertEquals($this->brands, $brandsFound['brands']);
        $this->assertNull($brandsFound['nothing']);

        // POST
        $namesFound = $handler->all(['names', 'nothing']);

        $this->assertArrayHasKey('names', $namesFound);
        $this->assertArrayHasKey('nothing', $namesFound);
        $this->assertEquals($this->names, $namesFound['names']);
        $this->assertNull($namesFound['nothing']);

        // DEFAULT VALUE
        $nonExisting = $handler->all([
            'non-existing'
        ]);

        $this->assertArrayHasKey('non-existing', $nonExisting);
        $this->assertNull($nonExisting['non-existing']);

        // Reset
        $_GET = [];
        $_POST = [];
    }

}