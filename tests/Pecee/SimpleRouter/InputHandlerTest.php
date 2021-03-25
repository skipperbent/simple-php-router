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

    protected $employees = [
        'Samsung' => 287000,
        'Apple' => 132000,
        'HP' => 53000,
        'Canon' => 197000.99,
    ];

    protected $sodas = [
        0 => 'Pepsi',
        1 => 'Coca Cola',
        2 => 'Harboe',
        3 => 'Mountain Dew',
    ];

    protected $day = 'monday';

    public function testPost()
    {
        global $_POST;

        $_POST = [
            'names' => $this->names,
            'day' => $this->day,
            'sodas' => $this->sodas,
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
        $this->assertEquals($this->sodas, $handler->value('sodas'));

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

    public function testValidation()
    {
        global $_POST;

        $_POST = [
            'names' => $this->names,
            'day' => $this->day,
            'employees' => $this->employees,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');

        $handler = TestRouter::request()->getInputHandler();

        $this->assertInstanceOf(\Pecee\Http\Input\InputValidator::class, $handler->find('day')->validate());
        $this->assertEquals(true, $handler->find('day')->validate(true)->require()->isString()->minLength(6)->maxLength(6)->valid());
        $this->assertCount(4, $handler->find('day')->validate(true)->isNull()->isInteger()->minLength(8)->maxLength(5)->getErrors());
        $this->assertEquals(true, empty($handler->find('day')->validate(true)->require()->isString()->minLength(6)->maxLength(6)->getErrors()));
        $this->assertEquals(true, $handler->find('names')->validate(true)->require()->isSequentialArray()->valid());
        $this->assertEquals(true, $handler->find('employees')->validate(true)->require()->isAssociativeArray()->valid());
        //tests for new inputhandler
        //$this->assertEquals(true, $handler->find('employees')->getInputItems()['Samsung']->validate()->require()->isInteger()->valid());
        //$this->assertEquals(true, $handler->find('employees')->getInputItems()['Canon']->validate()->require()->isFloat()->valid());

        //TODO add tests for every validation method
    }

    public function testGet()
    {
        global $_GET;
        global $_POST;

        $_GET = [
            'names' => $this->names,
            'day' => $this->day,
        ];

        $_POST = [];

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

        $testFile = $this->generateFile();

        $_FILES = [
            'test_input' => $testFile,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');
        $inputHandler = TestRouter::request()->getInputHandler();

        $testFileContent = md5(uniqid('test', false));

        $file = $inputHandler->file('test_input');

        $this->assertInstanceOf(InputFile::class, $file);
        $this->assertEquals($testFile['name'], $file->getFilename());
        $this->assertEquals($testFile['type'], $file->getType());
        $this->assertEquals($testFile['tmp_name'], $file->getTmpName());
        $this->assertEquals($testFile['error'], $file->getError());
        $this->assertEquals($testFile['size'], $file->getSize());
        $this->assertEquals(pathinfo($testFile['name'], PATHINFO_EXTENSION), $file->getExtension());

        file_put_contents($testFile['tmp_name'], $testFileContent);
        $this->assertEquals($testFileContent, $file->getContents());

        // Cleanup
        unlink($testFile['tmp_name']);
    }

    public function testFilesArray()
    {
        global $_FILES;

        $testFiles = [
            $file = $this->generateFile(),
            $file = $this->generateFile(),
            $file = $this->generateFile(),
            $file = $this->generateFile(),
            $file = $this->generateFile(),
        ];

        $_FILES = [
            'my_files' => $testFiles,
        ];

        $router = TestRouter::router();
        $router->reset();
        $router->getRequest()->setMethod('post');
        $inputHandler = TestRouter::request()->getInputHandler();

        $files = $inputHandler->file('my_files');
        $this->assertCount(5, $files);

        /* @var $file InputFile */
        foreach ($files as $key => $file) {

            $testFileContent = md5(uniqid('test', false));

            $this->assertInstanceOf(InputFile::class, $file);
            $this->assertEquals($testFiles[$key]['name'], $file->getFilename());
            $this->assertEquals($testFiles[$key]['type'], $file->getType());
            $this->assertEquals($testFiles[$key]['tmp_name'], $file->getTmpName());
            $this->assertEquals($testFiles[$key]['error'], $file->getError());
            $this->assertEquals($testFiles[$key]['size'], $file->getSize());
            $this->assertEquals(pathinfo($testFiles[$key]['name'], PATHINFO_EXTENSION), $file->getExtension());

            file_put_contents($testFiles[$key]['tmp_name'], $testFileContent);

            $this->assertEquals($testFileContent, $file->getContents());

            // Cleanup
            unlink($testFiles[$key]['tmp_name']);
        }

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

    protected function generateFile()
    {
        return [
            'name'     => uniqid('', false) . '.txt',
            'type'     => 'text/plain',
            'tmp_name' => sys_get_temp_dir() . '/phpYfWUiw',
            'error'    => 0,
            'size'     => rand(3, 40),
        ];
    }

    protected function generateFileContent()
    {
        return md5(uniqid('', false));
    }

}