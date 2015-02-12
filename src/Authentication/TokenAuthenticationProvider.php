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

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Authentication;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\RestApi\Authentication\TokenAuthenticationProviderBase;
use Rhubarb\RestApi\Response\TokenAuthorisationRequiredResponse;
use Rhubarb\Scaffolds\TokenBasedRestApi\Exceptions\TokenInvalidException;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Stem\Models\Model;

abstract class TokenAuthenticationProvider extends TokenAuthenticationProviderBase
{
    protected abstract function logUserIn(Model $user);

    /**
     * Returns true if the token is valid.
     *
     * @param $tokenString
     * @throws \Rhubarb\Crown\Exceptions\ForceResponseException
     * @return mixed
     */
    protected function isTokenValid($tokenString)
    {
        try {
            $user = ApiToken::validateToken($tokenString);

            // We need to make the login provider understand that we're now authenticated.
            $this->logUserIn($user);
        } catch (TokenInvalidException $er) {
            throw new ForceResponseException(new TokenAuthorisationRequiredResponse($this));
        }

        return true;
    }
}