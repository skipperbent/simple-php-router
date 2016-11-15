<?php
namespace Pecee\SimpleRouter;

interface ILoadableRoute {

    public function getUrl();
    public function setUrl($url);

}