<?php

use Pecee\Http\Request;

class DummyController
{
    public function index()
    {

    }


    public function method1()
    {

    }

    public function method2()
    {

    }

    public function method3()
    {
        echo 'method3';
    }

    public function param($param1 = null)
    {
        echo join(', ', func_get_args());
    }

    public function params($lang, $name)
    {
        echo join(', ', func_get_args());
    }

    public function paramRegex($path)
    {
        echo $path;
    }

    public function getTest()
    {
        echo 'getTest';
    }

    public function postTest()
    {
        echo 'postTest';
    }

    public function putTest()
    {
        echo 'putTest';
    }

}