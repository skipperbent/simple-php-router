<?php

namespace Pecee\Controllers;

use Pecee\Http\Request;

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
    public function store(Request $request);

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
    public function update(Request $request, $id);

    /**
     * @param mixed $id
     * @return mixed
     */
    public function destroy($id);

}