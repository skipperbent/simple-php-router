<?php

require_once 'Dummy/DummyMiddleware.php';

class DependencyInjectionTest extends \PHPUnit\Framework\TestCase
{
    public function testDependencyInjectionDevelopment()
    {
        $builder = new \DI\ContainerBuilder();
        $container = $builder
            ->useAutowiring(true)
            ->ignorePhpDocErrors(true)
            ->build();

        TestRouter::enableDependencyInjection($container);

        $className = null;

        TestRouter::get('/', function (DummyMiddleware $url) use (&$className) {
            $className = \get_class($url);
        });

        TestRouter::debug('/');

        $this->assertEquals(DummyMiddleware::class, $className);
    }

    public function testDependencyInjectionProduction()
    {
        $cacheDir = dirname(__DIR__, 2) . '/tmp';

        $builder = new \DI\ContainerBuilder();
        $builder
            ->enableCompilation($cacheDir)
            ->writeProxiesToFile(true, $cacheDir . '/proxies')
            ->ignorePhpDocErrors(true)
            ->useAutowiring(true);

        $container = $builder->build();

        TestRouter::enableDependencyInjection($container);

        $className = null;

        TestRouter::get('/', function (DummyMiddleware $url) use (&$className) {
            $className = \get_class($url);
        });

        TestRouter::debug('/');

        $this->assertEquals(DummyMiddleware::class, $className);
    }
}