<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Adapters;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\NotAuthorisedResponse;
use Rhubarb\RestApi\Adapters\ModelResourceAdapter;
use Rhubarb\RestApi\Adapters\ResourceAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Scaffolds\TokenBasedRestApi\Resources\TokenResource;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Crown\DateTime\RhubarbDateTime;

class RevokeTokenResourceAdapter extends ResourceAdapter
{
    public function post($payload, $params, WebRequest $request)
    {
        $token = $this->extractAuthorizationToken($request);

        try {
            $apiToken = ApiToken::findFirst(new Equals('Token', $token));
            $apiToken->Expires = new RhubarbDateTime('-10 seconds');
            $apiToken->save();
        } catch (RecordNotFoundException $ex) {
        }

        $response = new \stdClass();
        $response->status = true;

        return $response;
    }

    private function extractAuthorizationToken(WebRequest $request)
    {
        $token = '';
        if (!$request->header("Authorization")) {
            Log::debug("Authorization header missing. If using fcgi be sure to instruct Apache to include this header", "RESTAPI");
            return $token;
        }

        $authString = trim($request->header("Authorization"));

        if (stripos($authString, "token") !== 0 && stripos($authString, "bearer") !== 0) {
            return $token;
        }

        if (!preg_match("/(token|bearer)(=|\s+)\"?([[:alnum:]]+)\"?/i", $authString, $match)) {
            return $token;
        }

        $token = $match[3];

        return $token;
    }

    protected function countItems($rangeStart, $rangeEnd, $params, ?WebRequest $request)
    {

    }

    protected function getItems($rangeStart, $rangeEnd, $params, ?WebRequest $request)
    {

    }

    public function putResource($resource)
    {

    }

    public function makeResourceByIdentifier($id)
    {

    }

    public function makeResourceFromData($data)
    {

    }
}