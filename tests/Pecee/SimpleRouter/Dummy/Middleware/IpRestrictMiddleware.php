<?php

class IpRestrictMiddleware extends \Pecee\Http\Middleware\IpRestrictAccess {

    protected array $ipBlacklist = [
        '5.5.5.5',
        '8.8.*',
    ];

    protected array $ipWhitelist = [
        '8.8.2.2',
    ];

}