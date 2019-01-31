<?php

namespace Rhubarb\Scaffolds\TokenBasedRestApi;

use Rhubarb\Crown\Exceptions\SettingMissingException;
use Rhubarb\Crown\Settings;

class ApiSettings extends Settings
{
    public $tokenExpiration = '+1 day';
    public $extendTokenExpirationOnUse = false;

    public $jwtKey = '';

    public static function getJwtKey()
    {
        $settings = self::singleton();
        $key = $settings->jwtKey;

        if ($key == "") {
            throw new SettingMissingException(__CLASS__, "No jwt key is defined in ApiSettings");
        }

        return $key;
    }
}
