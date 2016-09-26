<?php
namespace Demo\Controllers;

class DefaultController {

    public function index() {

        // implement
        echo 'DefaultController -> index';

    }

    public function contact() {

        echo 'DefaultController -> contact';

    }

    public function companies($id = null) {

        echo 'DefaultController -> companies -> id: ' . $id;

    }

}