<?php

class TestBootManager implements \Pecee\SimpleRouter\IRouterBootManager
{

    protected $routes;
    protected $aliasUrl;

    public function __construct(array $routes, string $aliasUrl)
    {
        $this->routes = $routes;
        $this->aliasUrl = $aliasUrl;
    }

    /**
     * Called when router loads it's routes
     *
     * @param \Pecee\SimpleRouter\Router $router
     * @param \Pecee\Http\Request $request
     */
    public function boot(\Pecee\SimpleRouter\Router $router, \Pecee\Http\Request $request): void
    {
        foreach ($this->routes as $url) {
            // If the current url matches the rewrite url, we use our custom route

            if ($request->getUrl()->contains($url) === true) {
                $request->setRewriteUrl($this->aliasUrl);
            }

        }
    }
}