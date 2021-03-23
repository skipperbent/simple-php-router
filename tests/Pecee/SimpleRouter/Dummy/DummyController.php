<?php

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
        return 'method3';
    }

	public function param($params = null)
	{
		echo join(', ', func_get_args());
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