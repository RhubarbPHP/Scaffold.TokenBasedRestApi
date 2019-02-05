<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\Users;

use Rhubarb\RestApi\Adapters\EntityAdapterInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class DefaultUserEntityAdapter implements EntityAdapterInterface
{

    public static function list(Request $request, Response $response): Response
    {
        // TODO: Implement list() method.
    }

    public static function get($id, Request $request, Response $response): Response
    {
        // TODO: Implement get() method.
    }

    public static function post(Request $request, Response $response): Response
    {
        // TODO: Implement post() method.
    }

    public static function put($id, Request $request, Response $response): Response
    {
        // TODO: Implement put() method.
    }

    public static function delete($id, Request $request, Response $response): Response
    {
        // TODO: Implement delete() method.
    }
}
