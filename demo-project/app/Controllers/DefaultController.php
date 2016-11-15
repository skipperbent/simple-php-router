<?php
namespace Demo\Controllers;

class DefaultController {

    public function index() {

        // implement
        echo sprintf('DefaultController -> index (?fun=%s)', input()->get('fun'));

    }

    public function contact() {

        echo 'DefaultController -> contact';

    }

    public function companies($id = null) {

        echo 'DefaultController -> companies -> id: ' . $id;

    }

    public function notFound() {
        echo 'Page not found';
    }

}