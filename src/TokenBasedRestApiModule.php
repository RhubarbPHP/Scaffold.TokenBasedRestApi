<?php

/*
 *	Copyright 2018 RhubarbPHP
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

use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\RestApi\Exceptions\MethodNotAllowedException;
use Rhubarb\RestApi\RhubarbApiModule;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Tuupola\Middleware\JwtAuthentication;

class TokenBasedRestApiModule implements RhubarbApiModule
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * @var array
     */
    private $ignore;

    private $algorithm;

    public function __construct(string $secret, $ignore = ['.*/token'], string $algorithm = 'HS512')
    {
        $this->secret = $secret;
        $this->ignore = $ignore;
        $this->algorithm = $algorithm;
    }

    protected function validatePayload($decoded)
    {
        if ($decoded['expires'] < (new \DateTime())->getTimestamp()) {
            throw new \Exception('bad!', 401);
        }
    }

    protected function authenticate(Request $request): bool
    {
        $authHeader = $request->getHeader('Authorization')[0];
        list($user, $password) = explode(':', base64_decode(str_replace('Basic ', '', $authHeader)), 2);
        try {
            /** @var LoginProvider $login */
            $login = LoginProvider::getProvider();
            $login->login($user, $password);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function registerErrorHandlers(App $app)
    {

    }

    protected function createJWTMiddleWare(): JwtAuthentication
    {
        $self = $this;
        return new JwtAuthentication([
            'secret' => $this->secret,
            'ignore' => $this->ignore,
            'before' => function (ServerRequestInterface $request, $arguments) use ($self) {
                $self->validatePayload($arguments['decoded']);
            },
        ]);
    }

    public function registerMiddleware(App $app)
    {
        $app->add($this->createJWTMiddleWare());
    }

    public function registerRoutes(App $app)
    {
        $self = $this;

        $app->any('/token', function (Request $request, Response $response) use ($self) {
            if ($request->getMethod() !== 'POST') {
                throw new MethodNotAllowedException();
            }
            if ($self->authenticate($request)) {
                $expiry = new \DateTime();
                $expiry->add(new\DateInterval('P1D'));
                return $response
                    ->write(JWT::encode(
                        ['expires' => $expiry->getTimestamp()],
                        $self->secret,
                        $self->algorithm
                    ))
                    ->withStatus(201, 'Created');
            } else {
                return $response
                    ->withAddedHeader('WWW_Authenticate', 'Basic')
                    ->withStatus(401, 'Access Denied');
            }
        });
        $app->get('/me', function (Request $request, Response $response) {
            return $response->withJson(['hello' => 'world']);
        });
    }
}
