<?php

class ResponseException extends \Exception
{
    protected string $response;

    public function __construct(string $response)
    {
        $this->response = $response;
        parent::__construct('', 0);
    }

    public function getResponse(): string
    {
        return $this->response;
    }

}