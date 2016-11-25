<?php
namespace Pecee\Http\Input;

interface IInputItem
{

    public function getIndex();

    public function getName();

    public function getValue();

    public function __toString();

}