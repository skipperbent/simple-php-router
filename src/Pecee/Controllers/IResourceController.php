<?php

namespace Pecee\Controllers;

interface IResourceController
{

    /**
     * @return string|null
     */
    public function index(): ?string;

    /**
     * @param mixed $id
     * @return string|null
     */
    public function show($id): ?string;

    /**
     * @return string|null
     */
    public function store(): ?string;

    /**
     * @return string|null
     */
    public function create(): ?string;

    /**
     * View
     * @param mixed $id
     * @return string|null
     */
    public function edit($id): ?string;

    /**
     * @param mixed $id
     * @return string|null
     */
    public function update($id): ?string;

    /**
     * @param mixed $id
     * @return string|null
     */
    public function destroy($id): ?string;

}