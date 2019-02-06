<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Adapters\Users;

use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\RestApi\Adapters\Stem\LegacyStemEntityAdapter;

class DefaultUserEntityAdapter extends LegacyStemEntityAdapter
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
