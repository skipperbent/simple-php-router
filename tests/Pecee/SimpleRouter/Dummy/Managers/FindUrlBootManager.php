<?php

class FindUrlBootManager implements \Pecee\SimpleRouter\IRouterBootManager
{
    protected $result;

    public function __construct(&$result)
    {
        $this->result = &$result;
    }

    /**
     * Called when router loads it's routes
     *
     * @param \Pecee\SimpleRouter\Router $router
     * @param \Pecee\Http\Request $request
     */
    public function boot(\Pecee\SimpleRouter\Router $router, \Pecee\Http\Request $request): void
    {
        $contact = $router->findRoute('contact');

        if($contact !== null) {
            $this->result = true;
        }
    }
}