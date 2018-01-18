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

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Resources;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Response\NotAuthorisedResponse;
use Rhubarb\RestApi\Resources\RestResource;
use Rhubarb\RestApi\UrlHandlers\RestHandler;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;

class TokenResource extends RestResource
{
    protected $loginProvider = "";
    protected $tokenToDelete = "";

    public function __construct($loginProvider, $tokenToDelete = '')
    {
        parent::__construct();

        $this->loginProvider = $loginProvider;
        $this->tokenToDelete = $tokenToDelete;
    }

    public function validateRequestPayload($payload, $method)
    {
        // Creating a token presently doesn't require any payload - override the default
        // validation to make sure this is allowed.
    }

    public function post($restResource, RestHandler $handler = null)
    {
        $loginProvider = $this->loginProvider;

        if (!$loginProvider->isLoggedIn()) {
            throw new ForceResponseException(new NotAuthorisedResponse($this));
        }

        $model = $loginProvider->getModel();

        $token = ApiToken::createToken($model, (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : "cli");

        $response = new \stdClass();
        $response->token = $token->Token;
        $response->expires = $token->Expires;

        return $response;
    }

    public function delete()
    {
        if (empty($this->tokenToDelete)) {
            parent::delete();
        }

        try {
            $apiToken = ApiToken::findFirst(new Equals('Token', $this->tokenToDelete));
            $apiToken->Expires = new RhubarbDateTime('-10 seconds');
            $apiToken->save();
        } catch (RecordNotFoundException $ex) {
        }

        $response = new \stdClass();
        $response->status = true;

        return $response;
    }
}
