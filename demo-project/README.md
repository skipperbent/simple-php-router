# Simple PHP router demo project

This project is here to give you a basic understanding of how to setup and using simple-php-router.

Please note that this demo-project only covers how to integrate the `simple-php-router` in a project without a framework. If you are using some sort of PHP framework in your project the implementation might vary.

**What we won't cover:**

- How to setup a solution that fits your need. This is a basic demo to help you get started.
- Understanding of MVC; including Controllers, Middlewares or ExceptionHandlers.
- How to integrate into third party frameworks.

**What we cover:**

- How to get up and running fast - from scratch.
- How to get ExceptionHandlers, Middlewares and Controllers working.
- How to setup your webservers.

## Installation

- Navigate to the `demo-project` folder in terminal and run `composer update` to install the latest version.
- Point your webserver to `demo-project/public`.

### Setting up Nginx

If you are using Nginx please make sure that url-rewriting is enabled.

You can easily enable url-rewriting by adding the following configuration for the Nginx configuration-file for the demo-project.

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Setting up Apache

Nothing special is required for Apache to work. We've include the `.htaccess` file in the `public` folder. If rewriting is not working for you, please check that the `mod_rewrite` module (htaccess support) is enabled in the Apache configuration.

## Folder structure

| Folder        | Description |
| ------------- |-------------|
| app            |Contains projects-specific PHP classes|
| public         |Public folder which are accessible through the web.|

## Notes

The demo project has it's own `Router` class implementation which extends the `SimpleRouter` class with further functionality. 
This class can be useful adding additional functionality that are required before and after routing occurs or any extra functionality belonging to the router itself. 

In this project we also use our custom router-class to autoload the `routes.php` file from our custom location (`app/routes.php`).

Please check the `routes.php` file in `demo-project/app` for all the urls/rules available in the project.

### CSRF-verifier

For the purpose of this demo, we've added a custom CSRF-verifier middleware called `CsrfVerifier` and disabled CSRF checks for all calls to `/api/*`. This will ensure that CSRF form-checks are not applied when calling our demo api url.

### Exception handlers

The included `CustomExceptionHandler` class returns a very basic json response for errors received on calls to `/api/*` or otherwise just a simple formatted error response.

### Middlewares

`ApiVerification` class is added to all calls to `/api/*`. This simple class just adds some data to the `Request` object, which is returned in one of the methods in the `ApiController` class. We've added this class to demonstrate that you can use middlewares to ensure that the user has the correct authentication - before router loads the controller itself.

### Urls

Please see `routes.php` for all routes and rules.

| URL        |
| ------------- |
| /             |
| /api/demo       |
| /companies       |
| /companies/[id]  |
| /contact         |

## The MIT License (MIT)

Copyright (c) 2016 Simon Sessingø / simple-php-router

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
