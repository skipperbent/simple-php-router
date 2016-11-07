<?php
namespace Demo\Controllers;

use Pecee\SimpleRouter\SimpleRouter;

class ApiController {

    public function index() {

        // The variable authenticated is set to true in the ApiVerification middleware class.

        $request = SimpleRouter::request();

        header('content-type: application/json');

        echo json_encode([
            'authenticated' => $request->authenticated
        ]);

    }

}