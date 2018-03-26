<?php

namespace Pecee\SimpleRouter\Route;

interface IControllerRoute extends IRoute
{
    /**
     * Get controller class-name
     *
     * @return string
     */
    public function getController(): string;

    /**
     * Set controller class-name
     *
     * @param string $controller
     * @return static
     */
    public function setController(string $controller): self;

}