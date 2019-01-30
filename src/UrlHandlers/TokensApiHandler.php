<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\UrlHandlers;

use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\MeResourceAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\TokenResourceAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\RevokeTokenResourceAdapter;
use Rhubarb\RestApi\UrlHandlers\RestApiHandler;
use Rhubarb\Scaffolds\TokenBasedRestApi\Middleware\CredentialsAuthenticationMiddleware;
use Rhubarb\Scaffolds\TokenBasedRestApi\Middleware\TokenAuthenticationMiddleware;

class TokensApiHandler extends RestApiHandler
{
    public function __construct(array $childUrlHandlers = [])
    {
        parent::__construct($childUrlHandlers);

        $this->post("/token", function($params, WebRequest $request){
            return (new TokenResourceAdapter())->post($request->getPayload(), $params, $request);
        })->with(new CredentialsAuthenticationMiddleware());

        $this->post("/token/revoke", function($params, WebRequest $request){
            return (new RevokeTokenResourceAdapter())->post($request->getPayload(), $params, $request);
        })->with(new TokenAuthenticationMiddleware());

        $this->get("/me", function($params, WebRequest $request){
            return (new MeResourceAdapter())->get(['id'=>''], $request);
        })->with(new TokenAuthenticationMiddleware());
    }

}