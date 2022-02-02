<?php

use Pecee\Http\Request;

class DummyInputValidator
{
	public function validator1(Request $request) : bool
	{
		if($request->getInputHandler()->exists('fullname'))
            return true;
        else
            return false;
	}

}