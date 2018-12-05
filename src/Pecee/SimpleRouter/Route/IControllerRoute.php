<?php

namespace Pecee\SimpleRouter\Route;

/**
 * Interface IControllerRoute
 *
 * @package Pecee\SimpleRouter\Route
 */
interface IControllerRoute extends IRoute
{
    /**
     * @return string
     */
    public function getController(): string;

    /**
     * @param string $controller
     * @return IControllerRoute
     */
    public function setController(string $controller): self;
}