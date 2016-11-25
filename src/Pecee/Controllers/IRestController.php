<?php
namespace Pecee\Controllers;

interface IRestController
{

    /**
     * @return void
     */
    function index();

    /**
     * @param mixed $id
     * @return void
     */
    function show($id);

    /**
     * @return void
     */
    function store();

    /**
     * @return void
     */
    function create();

    /**
     * View
     * @param mixed $id
     * @return void
     */
    function edit($id);

    /**
     * @param mixed $id
     * @return void
     */
    function update($id);

    /**
     * @param mixed $id
     * @return void
     */
    function destroy($id);

}