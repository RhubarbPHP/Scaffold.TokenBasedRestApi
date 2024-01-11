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
use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\RestApi\Exceptions\MethodNotAllowedException;
use Rhubarb\RestApi\RhubarbApiModule;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\Users\DefaultUserEntityAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\Users\UserEntityAdapter;
use Rhubarb\Stem\Schema\SolutionSchema;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteCollectorProxy;
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

    public function __construct(string $secret, $ignore = ['/token'], string $algorithm = 'HS512')
    {
        $this->secret = $secret;
        $this->ignore = $ignore;
        $this->algorithm = $algorithm;

        Container::current()->registerClass(UserEntityAdapter::class, DefaultUserEntityAdapter::class);
    }

    protected function validateUserRequest(Request $request, User $user): bool
    {
        return true;
    }

    protected function validateUser(User $user): bool
    {
        return true;
    }

    protected function validatePayload(Request $request, $decoded)
    {
        if ($decoded['expires'] < (new \DateTime())->getTimestamp()) {
            throw new \Exception('Session Expired', 401);
        }
        if (!$decoded['user']) {
            throw new \Exception('Invalid Token', 401);
        }
        /** @var LoginProvider $login */
        $login = LoginProvider::getProvider();
        /** @var User $user */
        $user = SolutionSchema::getModel(User::class, $decoded['user']);
        if (!$this->validateUser($user)) {
            throw new \Exception('Invalid User', 401);
        }
        if (!$this->validateUserRequest($request, $user)) {
            throw new \Exception('Access Denied', 403);
        }
        $login->forceLogin($user);
    }

    protected function authenticate(Request $request)
    {
        $authorizationHeader = $request->getHeader('Authorization');

        if (empty($authorizationHeader)) {
            return [false, 'Invalid payload'];
        }

        $authHeader = $request->getHeader('Authorization')[0];
        $loginCredentials = explode(':', base64_decode(str_replace('Basic ', '', $authHeader)), 2);

        if (count($loginCredentials) < 2) {
            return [false, 'Invalid payload'];
        }

        list($user, $password) = $loginCredentials;
        try {
            /** @var LoginProvider $login */
            $login = LoginProvider::getProvider();
            $login->login($user, $password);
            return [true, $login->loggedInUserIdentifier];
        } catch (\Exception $exception) {
            $message = '';

            if ($exception instanceof LoginFailedException) {
                $message = $exception->getPublicMessage();
            }

            return [false, $message];
        }
    }

    public function createJwtTokenForLoggedInUser($authData, $expiry = null) : string {
        $expiry = isset($expiry) ? $expiry : new \DateTime('now +1 day');

        return JWT::encode(
            [
                'expires' => $expiry->getTimestamp(),
                'user' => $authData,
            ],
            $this->secret,
            $this->algorithm
        );
    }

    public function registerErrorHandlers(App $app)
    {

    }

    protected function createJWTMiddleWare(): JwtAuthentication
    {
        $self = $this;
        return new JwtAuthentication([
            'secure' => false,
            'secret' => $this->secret,
            'ignore' => $this->ignore,
            'before' => function (Request $request, $arguments) use ($self) {
                $self->validatePayload($request, $arguments['decoded']);
            },
            'cookie' => 'AuthToken',
        ]);
    }

    public function registerMiddleware(RouteCollectorProxy $app)
    {
        $app->add($this->createJWTMiddleWare());
    }

    public function registerRoutes(RouteCollectorProxy $group)
    {
        $self = $this;

        $group->any('/token/', function (Request $request, Response $response) use ($self) {
            if ($request->getMethod() == 'OPTIONS') {
                return;
            }

            if ($request->getMethod() !== 'POST') {
                throw new MethodNotAllowedException();
            }
            
            list($status, $authData) = $self->authenticate($request);
            $rememberMe = $request->getParsedBody()['rememberMe'];
            $expiry = $rememberMe ? new \DateTime('now +30 day'): new \DateTime('now +1 day');

            if ($status) {
                $data = [
                    'token' => $self->createJwtTokenForLoggedInUser($authData, $expiry)
                ];

                $payload = json_encode($data);
                $response->getBody()->write($payload);
                return $response->withStatus(201, 'Created');
            } else {
                $payload = json_encode(['message' => $authData]);
                $response->getBody()->write($payload);
                return $response
                    ->withAddedHeader('WWW_Authenticate', 'Basic')
                    ->withStatus(401, 'Access Denied');
            }
        });
        $group->get('/me/', function (Request $request, Response $response) {
            /** @var LoginProvider $login */
            $login = LoginProvider::getProvider();
            $adapter = new UserEntityAdapter();
            return $adapter->get($request, $response, $login->loggedInUserIdentifier);
        });
        $group->put('/me/', function (Request $request, Response $response) {
            /** @var LoginProvider $login */
            $login = LoginProvider::getProvider();
            $adapter = new UserEntityAdapter();
            return $adapter->put($request, $response, $login->loggedInUserIdentifier, $login->loggedInUserIdentifier);
        });
    }
}
