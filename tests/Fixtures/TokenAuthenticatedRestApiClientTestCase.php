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

    abstract protected function GetUsername();

    abstract protected function GetPassword();

    protected function GetToken()
    {
        return false;
    }

    public function MakeApiCall( $uri, $method = "get", $payload = null )
    {
        $client = new TokenAuthenticatedRestClient(
            $this->GetApiUri(),
            $this->GetUsername(),
            $this->GetPassword(),
            $this->GetTokensUri()
        );

        $token = $this->GetToken();

        if ( $token )
        {
            $client->SetToken( $token );
        }

        $request = new RestHttpRequest( $uri, $method, $payload );
        $response = $client->MakeRequest( $request );

        return $response;
    }
} 