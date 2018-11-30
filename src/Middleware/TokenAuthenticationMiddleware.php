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

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Middleware;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\Response;
use Rhubarb\RestApi\Middleware\Middleware;
use Rhubarb\Scaffolds\TokenBasedRestApi\Exceptions\TokenInvalidException;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Scaffolds\TokenBasedRestApi\Responses\TokenAuthorisationRequiredResponse;
use Rhubarb\Stem\Models\Model;

class TokenAuthenticationMiddleware extends Middleware
{
    protected function logUserIn(Model $user)
    {
        $loginProvider = LoginProvider::getProvider();
        $loginProvider->forceLogin($user);
    }

    public function handleRequest(WebRequest $request, callable $next): ?Response
    {
        if (!$request->header("Authorization")) {
            Log::debug("Authorization header missing. If using fcgi be sure to instruct Apache to include this header", "RESTAPI");
            return new TokenAuthorisationRequiredResponse();
        }

        $authString = trim($request->header("Authorization"));

        if (stripos($authString, "token") !== 0) {
            return new TokenAuthorisationRequiredResponse();
        }

        if (!preg_match("/token=\"?([[:alnum:]]+)\"?/", $authString, $match)) {
            return new TokenAuthorisationRequiredResponse();
        }

        $token = $match[1];

        if (!$this->isTokenValid($token)) {
            return new TokenAuthorisationRequiredResponse();
        }

        return null;
    }

    protected function isTokenValid($tokenString): bool
    {
        try {
            $user = ApiToken::validateToken($tokenString);

            // We need to make the login provider understand that we're now authenticated.
            $this->logUserIn($user);
        } catch (TokenInvalidException $er) {
            return false;
        }

        return true;
    }
}
