<?php
namespace Demo\Controllers;

use Pecee\Http\Request;

class ApiController {

    public function index() {

        // The variable authenticated is set to true in the ApiVerification middleware class.

        $request = Request::getInstance();

        header('content-type: application/json');

        echo json_encode([
            'authenticated' => $request->authenticated
        ]);

    }

}