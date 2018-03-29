<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';
require_once 'Dummy/Security/SilentTokenProvider.php';

use \Pecee\SimpleRouter\Handlers\EventHandler;
use \Pecee\SimpleRouter\Event\EventArgument;

class EventHandlerTest extends \PHPUnit\Framework\TestCase
{

    public function testMissingEvents() {

        $events = EventHandler::$events;

        // Remove the all event
        unset($events[\array_search(EventHandler::EVENT_ALL, $events, true)], $events[\array_search(EventHandler::EVENT_REWRITE, $events, true)]);

        $eventHandler = new EventHandler();
        $eventHandler->register(EventHandler::EVENT_ALL, function(EventArgument $arg) use(&$events) {
            $key = \array_search($arg->getEventName(), $events, true);
            unset($events[$key]);
        });

        TestRouter::addEventHandler($eventHandler);

        TestRouter::error(function (\Pecee\Http\Request $request, \Exception $error) {

            // Trigger rewrite
            $request->setRewriteUrl('/');

        });

        TestRouter::get('/', 'DummyController@method1')->name('home');

        TestRouter::router()->findRoute('home');
        TestRouter::router()->getUrl('home');

        $csrfVerifier = new \Pecee\Http\Middleware\BaseCsrfVerifier();
        $csrfVerifier->setTokenProvider(new SilentTokenProvider());
        TestRouter::csrfVerifier($csrfVerifier);

        TestRouter::debug('/not-existing');

        $this->assertEquals($events, []);

        TestRouter::router()->reset();

    }

    public function testAllEvent() {

        $status = 0;

        $eventHandler = new EventHandler();
        $eventHandler->register(EventHandler::EVENT_ALL, function(EventArgument $arg) use(&$status) {
            $status++;
        });

        TestRouter::addEventHandler($eventHandler);

        TestRouter::get('/', 'DummyController@method1');
        TestRouter::debug('/');

        // All event should fire for each other event
        $this->assertEquals(\count(EventHandler::$events), $status);
    }

    public function testEvents() {
        $this->assertEquals(true, true);
    }

}