<?php
class ResourceController implements \Pecee\Controllers\IResourceController
{

    public function index() : ?string
    {
        echo 'index';
        return null;
    }

    public function show($id) : ?string
    {
        echo 'show ' . $id;
        return null;
    }

    public function store() : ?string
    {
        echo 'store';
        return null;
    }

    public function create() : ?string
    {
        echo 'create';
        return null;
    }

    public function edit($id) : ?string
    {
        echo 'edit ' . $id;
        return null;
    }

    public function update($id) : ?string
    {
        echo 'update ' . $id;
        return null;
    }

    public function destroy($id) : ?string
    {
        echo 'destroy ' . $id;
        return null;
    }
}