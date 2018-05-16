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

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Authentication\LoginProviderBasedAuthenticationProviders;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Response\BasicAuthorisationRequiredResponse;
use Rhubarb\Crown\Response\ExpiredResponse;
use Rhubarb\Crown\Response\TooManyLoginAttemptsResponse;
use Rhubarb\RestApi\Authentication\CredentialsLoginProviderAuthenticationProvider;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginExpiredException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginTemporarilyLockedOutException;


class LoginProviderCredentialsAuthenticationProvider extends CredentialsLoginProviderAuthenticationProvider
{
    protected function getLoginProviderClassName()
    {
        return LoginProvider::class;
    }

    public function authenticate(Request $request)
    {
        try {
            return parent::authenticate($request);
        } catch (LoginExpiredException $ex) {
            throw new ForceResponseException(new ExpiredResponse("API"));
        } catch (LoginTemporarilyLockedOutException $ex) {
            throw new ForceResponseException(new TooManyLoginAttemptsResponse("API"));
        } catch (LoginFailedException $ex) {
            throw new ForceResponseException(new BasicAuthorisationRequiredResponse("API"));
        }
    }
}
