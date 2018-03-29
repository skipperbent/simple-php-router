<?php

class SilentTokenProvider implements \Pecee\Http\Security\ITokenProvider {

    protected $token;

    public function __construct()
    {
        $this->refresh();
    }

    /**
     * Refresh existing token
     */
    public function refresh(): void
    {
        $this->token = uniqid('', false);
    }

    /**
     * Validate valid CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool
    {
        return ($token === $this->token);
    }

    /**
     * Get token token
     *
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getToken(?string $defaultValue = null): ?string
    {
        return $this->token ?? $defaultValue;
    }
}