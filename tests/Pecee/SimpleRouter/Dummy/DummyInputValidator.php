<?php

use Pecee\Http\Request;

class DummyInputValidator implements \Pecee\Http\Input\IInputValidator
{
	public function handle(Request $request) : bool
	{
		if($request->getInputHandler()->exists('fullname'))
            return true;
        else
            return false;
	}

}