<?php

class DummyController {

    public function start() {
        echo static::class . '@' .'start() OK';
    }

    public function param($params = null) {
        $params = func_get_args();
        echo 'Params: ' . join(', ', $params);
    }

    public function notFound() {
        echo 'not found';
    }

}