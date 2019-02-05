<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\Users;

use Rhubarb\RestApi\Adapters\EntityAdapterInterface;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Models\Model;
use Slim\Http\Request;
use Slim\Http\Response;

class DefaultUserEntityAdapter implements EntityAdapterInterface
{
    protected static function getModelClass(): string
    {
        return User::class;
    }

    protected static function getEntityForId($id): Model
    {
        /** @var Model $modelClass */
        $modelClass = self::getModelClass();
        return new $modelClass($id);
    }

    protected static function getPayloadForEntity(Model $model): array
    {
        return $model->exportPublicData();
    }

    protected static function createEntity($payload, $id = null): Model
    {
        $modelClass = self::getModelClass();
        /** @var Model $model */
        $model = new $modelClass($id);
        $model->importData($payload);
        $model->save();
        return $model;
    }

    protected static function getEntityList(int $offset, int $pageSize, Request $request): Collection
    {
        /** @var Model $modelClass */
        $modelClass = self::getModelClass();
        return $modelClass::all()->setRange($offset, $pageSize);
    }

    protected static function getPayloadForEntityList(Collection $collection, $request): array
    {
        $payloads = [];
        foreach ($collection as $entity) {
            $payloads[] = self::getPayloadForEntity($entity);
        }
        return $payloads;
    }

    public static function list(Request $request, Response $response): Response
    {
        $offset = (int)$request->getQueryParam('offset', $request->getQueryParam('from', 1) - 1);
        $pageSize = (int)$request->getQueryParam('pageSize', $request->getQueryParam('to', 10 - $offset));

        $list = self::getEntityList(
            $offset,
            $pageSize,
            $request
        );
        $total = $list->count();
        return $response
            ->withJson(self::getPayloadForEntityList($list, $request))
            ->withAddedHeader('X-Total', $total)
            ->withAddedHeader('X-Offset', $offset)
            ->withAddedHeader('X-PageSize', $pageSize)
            ->withAddedHeader('X-From', $offset + 1)
            ->withAddedHeader('X-To', $offset + $pageSize);
    }

    public static function get($id, Request $request, Response $response): Response
    {
        return $response->withJson(self::getPayloadForEntity(self::getEntityForId($id)));
    }

    public static function post(Request $request, Response $response): Response
    {
        return $response->withJson(self::getPayloadForEntity(
            self::createEntity($request->getParsedBody())
        ));
    }

    public static function put($id, Request $request, Response $response): Response
    {
        return $response->withJson(self::getPayloadForEntity(
            self::createEntity($request->getParsedBody(), $id)
        ));
    }

    public static function delete($id, Request $request, Response $response): Response
    {
        (self::getEntityForId($id))->delete();

        return $response;
    }
}
