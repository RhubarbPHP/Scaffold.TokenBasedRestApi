<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Tests\Fixtures;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\RestApi\Clients\RestHttpRequest;
use Rhubarb\RestApi\Clients\TokenAuthenticatedRestClient;

abstract class TokenAuthenticatedRestApiClientTestCase extends RhubarbTestCase
{
    protected function getApiUri()
    {
        return "/api";
    }

    protected function getTokensUri()
    {
        return "/tokens";
    }

    abstract protected function getUsername();

    abstract protected function getPassword();

    protected function getToken()
    {
        return false;
    }

    public function makeApiCall($uri, $method = "get", $payload = null)
    {
        $client = new TokenAuthenticatedRestClient(
            $this->getApiUri(),
            $this->getUsername(),
            $this->getPassword(),
            $this->getTokensUri()
        );

        $token = $this->getToken();

        if ($token) {
            $client->setToken($token);
        }

        $request = new RestHttpRequest($uri, $method, $payload);
        $response = $client->makeRequest($request);

        return $response;
    }
} 
