<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi;

use Rhubarb\Crown\Settings;

class ApiSettings extends Settings
{
    public $tokenExpiration = '+1 day';
    public $extendTokenExpirationOnUse = false;
}
