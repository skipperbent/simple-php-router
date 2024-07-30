<?php
class ResourceController implements \Pecee\Controllers\IResourceController
{

    public function index() : ?string
    {
        return 'index';
    }

    public function show(mixed $id) : ?string
    {
        return 'show ' . $id;
    }

    public function store() : ?string
    {
        return 'store';
    }

    public function create() : ?string
    {
        return 'create';
    }

    public function edit(mixed $id) : ?string
    {
        return 'edit ' . $id;
    }

    public function update(mixed $id) : ?string
    {
        return 'update ' . $id;
    }

    public function destroy(mixed $id) : ?string
    {
        return 'destroy ' . $id;
    }
}