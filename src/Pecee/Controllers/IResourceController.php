<?php

namespace Pecee\Controllers;

/**
 * Interface IResourceController
 *
 * @package Pecee\Controllers
 */
interface IResourceController
{
    /**
     * @return string|null
     */
    public function index(): ?string;

    /**
     * @param $id
     * @return null|string
     */
    public function show($id): ?string;

    /**
     * @return null|string
     */
    public function store(): ?string;

    /**
     * @return null|string
     */
    public function create(): ?string;

    /**
     * @param $id
     * @return null|string
     */
    public function edit($id): ?string;

    /**
     * @param $id
     * @return null|string
     */
    public function update($id): ?string;

    /**
     * @param $id
     * @return null|string
     */
    public function destroy($id): ?string;
}