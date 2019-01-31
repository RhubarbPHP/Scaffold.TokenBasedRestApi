<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\UrlHandlers;

use Firebase\JWT\JWT;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\MeResourceAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\RevokeTokenResourceAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\TokenResourceAdapter;
use Rhubarb\RestApi\UrlHandlers\RestApiHandler;
use Rhubarb\Scaffolds\TokenBasedRestApi\ApiSettings;
use Rhubarb\Scaffolds\TokenBasedRestApi\Middleware\CredentialsAuthenticationMiddleware;
use Rhubarb\Scaffolds\TokenBasedRestApi\Middleware\TokenAuthenticationMiddleware;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;

class TokensApiHandler extends RestApiHandler
{
    public function __construct(array $childUrlHandlers = [])
    {
        parent::__construct($childUrlHandlers);

        $this->post("/token", function($params, WebRequest $request){

            $loginProvider = LoginProvider::getProvider();
            $model = $loginProvider->getModel();

            try {
                $token = new ApiToken($model->getUniqueIdentifier());
            } catch (RecordNotFoundException $er){
                $token = new ApiToken();
                $token->UserID = $model->getUniqueIdentifier();
                $token->Token = md5(uniqid());// TODO Just for testing...
                $token->save();
            }

            // Create a JWT token.
            $jwt =  new \stdClass();
            $jwt->id = $model->getUniqueIdentifier();
            $jwt->authToken = $token->Token;

            $encoded = JWT::encode($jwt, ApiSettings::getJwtKey());

            return [
                "token" => $encoded
            ];
        })->with(new CredentialsAuthenticationMiddleware());

        $this->delete("/token", function($params, WebRequest $request){
            return (new TokenResourceAdapter())->delete($request->getPayload(), $params, $request);
        })->with(new TokenAuthenticationMiddleware());

        $this->get("/me", function($params, WebRequest $request){
            return (new MeResourceAdapter())->get(['id'=>''], $request);
        })->with(new TokenAuthenticationMiddleware());
    }

}