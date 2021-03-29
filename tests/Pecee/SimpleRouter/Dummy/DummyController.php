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

    public function method4($param1, $param2)
    {

    }

    public function param($params = null)
    {
        echo join(', ', func_get_args());
    }

    public function paramPlain($param)
    {

    }

    public function methodAviso($aviso)
    {

    }

    public function methodPagina($pagina = null)
    {

    }

    public function param1($param1)
    {
        echo 'param1';
    }

    public function paramLangName($lang, $name)
    {
        echo $lang . ', ' . $name;
    }

    public function methodPath($path)
    {

    }

    public function methodId($id = null)
    {

    }

    public function methodListado($listado, $category)
    {

    }

    public function paramPath($path)
    {
        return $path;
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