<?php

namespace Pecee\Controllers;

interface IResourceController
{

    /**
     * @return mixed
     */
    public function index();

    /**
     * @param mixed $id
     * @return mixed
     */
    public function show($id);

    /**
     * @return mixed
     */
    public function store();

    /**
     * @return mixed
     */
    public function create();

    /**
     * View
     * @param mixed $id
     * @return mixed
     */
    public function edit($id);

    /**
     * @param mixed $id
     * @return mixed
     */
    public function update($id);

    /**
     * @param mixed $id
     * @return mixed
     */
    public function destroy($id);

}