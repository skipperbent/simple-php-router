<?php

class DummyController
{
	public function method1()
	{

	}

    public function method2()
    {

    }

	public function param($params = null)
	{
		echo join(', ', func_get_args());
	}

}