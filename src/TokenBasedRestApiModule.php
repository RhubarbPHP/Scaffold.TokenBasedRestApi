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

namespace Rhubarb\Scaffolds\TokenBasedRestApi;

use Rhubarb\Crown\Module;
use Rhubarb\RestApi\Authentication\AuthenticationProvider;
use Rhubarb\RestApi\UrlHandlers\RestResourceHandler;
use Rhubarb\Scaffolds\TokenBasedRestApi\Resources\DeleteTokenResource;
use Rhubarb\Scaffolds\TokenBasedRestApi\UrlHandlers\TokenCreationUrlHandler;
use Rhubarb\Stem\Schema\SolutionSchema;

class TokenBasedRestApiModule extends Module
{
    private $loginProviderAuthenticationProviderClassName = "";
    private $tokenAuthenticationProviderClassName = "";
    private $apiStubUrl;
    private $tokenResourceClassName;

    private static $authenticationUserModelName = "User";

    public function __construct(
        $loginProviderAuthenticationProviderClassName = '\Rhubarb\Scaffolds\TokenBasedRestApi\Authentication\LoginProviderBasedAuthenticationProviders\LoginProviderCredentialsAuthenticationProvider',
        $tokenAuthenticationProviderClassName = '\Rhubarb\Scaffolds\TokenBasedRestApi\Authentication\LoginProviderBasedAuthenticationProviders\LoginProviderTokenAuthenticationProvider',
        $apiStubUrl = "/api/",
        $authenticationUserModelName = "User",
        $tokenResourceClassName = '\Rhubarb\Scaffolds\TokenBasedRestApi\Resources\TokenResource'
    ) {
        parent::__construct();

        $this->apiStubUrl = $apiStubUrl;
        $this->loginProviderAuthenticationProviderClassName = $loginProviderAuthenticationProviderClassName;
        $this->tokenAuthenticationProviderClassName = $tokenAuthenticationProviderClassName;
        $this->tokenResourceClassName = $tokenResourceClassName;

        self::$authenticationUserModelName = $authenticationUserModelName;
    }

    /**
     * @return string
     */
    public static function getAuthenticationUserModelName()
    {
        return self::$authenticationUserModelName;
    }

    protected function initialise()
    {
        parent::initialise();

        SolutionSchema::registerSchema("TokenBasedRestApi",
            '\Rhubarb\Scaffolds\TokenBasedRestApi\Model\TokenBasedRestApiSolutionSchema');

        AuthenticationProvider::setProviderClassName($this->tokenAuthenticationProviderClassName);

        $tokenHandler = new TokenCreationUrlHandler($this->loginProviderAuthenticationProviderClassName, [
            ], $this->tokenResourceClassName);
        $tokenHandler->setName("tokens");
        $tokenHandler->setPriority(1000);

        // Register the url that serves up the tokens.
        $this->addUrlHandlers(
            [
                $this->apiStubUrl . "tokens" => $tokenHandler,
            ]);
    }
}
