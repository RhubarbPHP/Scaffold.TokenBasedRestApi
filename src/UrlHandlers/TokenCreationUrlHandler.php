<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Scaffolds\TokenBasedRestApi\UrlHandlers;

use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\Response;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;

class TokenCreationUrlHandler extends RestResourceHandler
{
    private $authenticationProviderClassName;
    private $urlToken;

    public function __construct(
        $authenticationProviderClassName,
        $childUrlHandlers = [],
        $tokenResourceClassName = '\Rhubarb\Scaffolds\TokenBasedRestApi\Resources\TokenResource'
    ) {
        $this->authenticationProviderClassName = $authenticationProviderClassName;

        parent::__construct($tokenResourceClassName, $childUrlHandlers);
    }

    protected function getSupportedHttpMethods()
    {
        return ["post", "delete"];
    }

    protected function getRestResource()
    {
        $className = $this->apiResourceClassName;
        $authenticationProvider = $this->createAuthenticationProvider();
        $loginProvider = $authenticationProvider->getLoginProvider();
        $resource = new $className($loginProvider);

        if (!empty($this->urlToken)) {
            if (property_exists($resource, 'tokenToDelete')) {
                $resource->tokenToDelete = $this->urlToken;
            }
        }

        return $resource;
    }

    protected function createAuthenticationProvider()
    {
        $class = $this->authenticationProviderClassName;

        return new $class();
    }

    protected function authenticate(Request $request)
    {
        $method = strtolower($request->server("REQUEST_METHOD"));
        if ($method == 'delete') {
            return true;
        }

        return parent::authenticate($request);
    }

    protected function handleDelete(WebRequest $request, Response $response)
    {
        //  Extract Token from URL
        $urlParts = explode('/', $request->urlPath);
        $urlParts = array_values(array_filter($urlParts, function ($value) {
            return !empty($value);
        }));
        $this->urlToken = $urlParts[count($urlParts) - 1];

        return parent::handleDelete($request, $response);
    }
}
