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
     * @return static
     */
    public function setController(string $controller): self;
}