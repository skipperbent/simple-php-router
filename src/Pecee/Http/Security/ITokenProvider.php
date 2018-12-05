<?php

namespace Pecee\Http\Security;

/**
 * Interface ITokenProvider
 *
 * @package Pecee\Http\Security
 */
interface ITokenProvider
{
    public function refresh(): void;

    /**
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool;

    /**
     * @param null|string $defaultValue
     * @return null|string
     */
    public function getToken(?string $defaultValue = null): ?string;
}