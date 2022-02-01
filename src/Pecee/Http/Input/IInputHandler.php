<?php

namespace Pecee\Http\Input;

use Pecee\Http\Request;

interface IInputHandler{

    public function parseInputs(Request $request): void;

    public function find(string $index, ...$methods);

    public function value(string $index, $defaultValue = null, ...$methods);

    public function exists(string $index, ...$methods): bool;

    public function post(string $index, $defaultValue = null);

    public function file(string $index, $defaultValue = null);

    public function get(string $index, $defaultValue = null);

    public function all(array $filter = []): array;
}