<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use \Pecee\SimpleRouter\SimpleRouter;

SimpleRouter::get('/user/{name}', 'UserController@show')->where(['name' => '[\w]+']);
$debugInfo = SimpleRouter::startDebug();
echo sprintf('<pre>%s</pre>', var_export($debugInfo, true));
exit;