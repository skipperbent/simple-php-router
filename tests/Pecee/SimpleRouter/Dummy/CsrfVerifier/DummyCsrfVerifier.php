<?php

class DummyCsrfVerifier extends \Pecee\Http\Middleware\BaseCsrfVerifier {

    protected array $except = [
        '/exclude-page',
        '/exclude-all/*',
    ];

    protected array $include = [
        '/exclude-all/include-page',
    ];

    public function testSkip(\Pecee\Http\Request $request) {
        return $this->skip($request);
    }

}