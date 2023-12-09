<?php

namespace Pecee\Http\Middleware;

use Pecee\Http\Middleware\Exceptions\TokenMismatchException;
use Pecee\Http\Request;
use Pecee\Http\Security\CookieTokenProvider;
use Pecee\Http\Security\ITokenProvider;

class BaseCsrfVerifier implements IMiddleware
{
    public const POST_KEY = 'csrf_token';
    public const HEADER_KEY = 'X-CSRF-TOKEN';

    /**
     * Urls to ignore. You can use * to exclude all sub-urls on a given path.
     * For example: /admin/*
     * @var array|null
     */
    protected array $except = [];

    /**
     * Urls to include. Can be used to include urls from a certain path.
     * @var array|null
     */
    protected array $include = [];

    /**
     * @var ITokenProvider
     */
    protected ITokenProvider $tokenProvider;

    /**
     * BaseCsrfVerifier constructor.
     */
    public function __construct()
    {
        $this->tokenProvider = new CookieTokenProvider();
    }

    protected function isIncluded(Request $request): bool
    {
        if (count($this->include) > 0) {
            foreach ($this->include as $includeUrl) {
                $includeUrl = rtrim($includeUrl, '/');
                if ($includeUrl[strlen($includeUrl) - 1] === '*') {
                    $includeUrl = rtrim($includeUrl, '*');
                    return $request->getUrl()->contains($includeUrl);
                }

                return ($includeUrl === rtrim($request->getUrl()->getRelativeUrl(false), '/'));
            }
        }

        return false;
    }

    /**
     * Check if the url matches the urls in the except property
     * @param Request $request
     * @return bool
     */
    protected function skip(Request $request): bool
    {
        if (count($this->except) === 0) {
            return false;
        }

        foreach ($this->except as $url) {
            $url = rtrim($url, '/');
            if ($url[strlen($url) - 1] === '*') {
                $url = rtrim($url, '*');
                $skip = $request->getUrl()->contains($url);
            } else {
                $skip = ($url === rtrim($request->getUrl()->getRelativeUrl(false), '/'));
            }

            if ($skip === true) {

                $skip = !$this->isIncluded($request);

                if ($skip === false) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Handle request
     *
     * @param Request $request
     * @throws TokenMismatchException
     */
    public function handle(Request $request): void
    {
        if ($this->skip($request) === false && ($request->isPostBack() === true || $request->isPostBack() === true && $this->isIncluded($request) === true)) {

            $token = $request->getInputHandler()->value(
                static::POST_KEY,
                $request->getHeader(static::HEADER_KEY),
            );

            if ($this->tokenProvider->validate((string)$token) === false) {
                throw new TokenMismatchException('Invalid CSRF-token.');
            }

        }

        // Refresh existing token
        $this->tokenProvider->refresh();
    }

    public function getTokenProvider(): ITokenProvider
    {
        return $this->tokenProvider;
    }

    /**
     * Set token provider
     * @param ITokenProvider $provider
     */
    public function setTokenProvider(ITokenProvider $provider): void
    {
        $this->tokenProvider = $provider;
    }

}