<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Adapters;

use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\RestApi\Adapters\ModelResourceAdapter;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\TokenBasedRestApi\Resources\UserResource;

class MeResourceAdapter extends ModelResourceAdapter
{
    public function __construct()
    {
        parent::__construct(UserResource::class, User::class);
    }

    public function makeResourceByIdentifier($id)
    {
        $login = LoginProvider::getProvider();
        return parent::makeResourceFromData($login->getModel());
    }
}