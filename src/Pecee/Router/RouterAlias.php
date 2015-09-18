<?php
namespace Pecee\Router;
abstract class RouterAlias {
	abstract public function getPath($currentPath);
	abstract public function getUrl($currentUrl);	
}