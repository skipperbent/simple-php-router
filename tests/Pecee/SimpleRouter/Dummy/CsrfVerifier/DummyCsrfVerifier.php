<?php

class DummyCsrfVerifier extends \Pecee\Http\Middleware\BaseCsrfVerifier {

    protected $except = [
        '/exclude-page',
        '/exclude-all/*',
    ];

    protected $include = [
        '/exclude-all/include-page',
    ];

    public function testSkip(\Pecee\Http\Request $request) {
        return $this->skip($request);
    }

}