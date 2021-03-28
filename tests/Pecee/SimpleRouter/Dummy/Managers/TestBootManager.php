<?php

class TestBootManager implements \Pecee\SimpleRouter\IRouterBootManager
{

    protected $rewrite;

    public function __construct(array $rewrite)
    {
        $this->rewrite = $rewrite;
    }

    /**
     * Called when router loads it's routes
     *
     * @param \Pecee\SimpleRouter\Router $router
     * @param \Pecee\Http\Request $request
     */
    public function boot(\Pecee\SimpleRouter\Router $router, \Pecee\Http\Request $request): void
    {
        foreach ($this->rewrite as $url => $rewrite) {
            // If the current url matches the rewrite url, we use our custom route

            if ($request->getUrl()->contains($url) === true) {
                $request->setRewriteUrl($rewrite);
            }

        }
    }
}