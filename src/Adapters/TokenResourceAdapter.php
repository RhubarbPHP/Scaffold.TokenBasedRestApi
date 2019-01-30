<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Adapters;

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\NotAuthorisedResponse;
use Rhubarb\RestApi\Adapters\ModelResourceAdapter;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Scaffolds\TokenBasedRestApi\Resources\TokenResource;
use Rhubarb\Stem\Filters\Equals;

class TokenResourceAdapter extends ModelResourceAdapter
{
    /**
     * @var string
     */
    private $loginProviderClassName;

    public function __construct(string $loginProviderClassName = "")
    {
        parent::__construct(TokenResource::class, ApiToken::class);
        $this->loginProviderClassName = $loginProviderClassName;
    }

    protected function getLoginProvider()
    {
        if ($this->loginProviderClassName){
            $provider = $this->loginProviderClassName;
            return new $provider();
        } else {
            return LoginProvider::getProvider();
        }
    }

    public function post($payload, $params, WebRequest $request)
    {
        $loginProvider = $this->getLoginProvider();

        if (!$loginProvider->isLoggedIn()) {
            throw new ForceResponseException(new NotAuthorisedResponse($this));
        }

        $model = $loginProvider->getModel();

        $token = ApiToken::createToken($model, (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : "cli");
        $resource = $this->makeResourceFromData($token);

        unset($resource->id);

        return $resource;
    }


    public function delete($payload, $params, ?WebRequest $request)
    {
        $token = $this->extractAuthorizationToken($request);

        try {
            $apiToken = ApiToken::findFirst(new Equals('Token', $token));
            $apiToken->delete();
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
}