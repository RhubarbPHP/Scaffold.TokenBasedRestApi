<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Model;

use Rhubarb\Scaffolds\TokenBasedRestApi\Exceptions\TokenInvalidException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Models\Validation\HasValue;
use Rhubarb\Stem\Models\Validation\Validator;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\Index;
use Rhubarb\Stem\Schema\ModelSchema;

class ApiToken extends Model
{
    const TOKEN_EXPIRATION = "+1 day";

    protected function createSchema()
    {
        $schema = new ModelSchema("tblApiToken");

        $schema->addColumn(
            new AutoIncrementColumn("ApiTokenID"),
            new ForeignKeyColumn("AuthenticatedUserID"),
            new StringColumn("Token", 100),
            new StringColumn("IpAddress", 20),
            new DateTimeColumn("Expires")
        );

        $schema->labelColumnName = "Token";

        $schema->addIndex(new Index("Token", Index::INDEX));

        return $schema;
    }

    /**
     * Validates a given token string is valid and returns the authenticated user model.
     *
     * @param $tokenString
     * @return mixed
     * @throws \Rhubarb\Scaffolds\TokenBasedRestApi\Exceptions\TokenInvalidException Thrown if the token is invalid.
     */
    public static function validateToken($tokenString)
    {
        $tokens = ApiToken::find(new AndGroup(
            [
                new Equals("Token", $tokenString),
                new GreaterThan("Expires", "now", true)
            ]
        ));

        if (count($tokens) != 1) {
            throw new TokenInvalidException();
        }

        $token = $tokens[0];

        return $token->AuthenticatedUser;
    }

    public static function createToken(Model $user, $ipAddress)
    {
        $tokenString = sha1(sha1("20s%xasD" . $user->UniqueIdentifier) . sha1(microtime() . $ipAddress));

        $token = new ApiToken();
        $token->AuthenticatedUserID = $user->UniqueIdentifier;
        $token->IpAddress = $ipAddress;
        $token->Token = $tokenString;
        $token->save();

        return $token;
    }

    /**
     * Looks up an existing valid token for the user at the specified IP address. If none is found, it
     * creates a new one.
     *
     * @param Model $user
     * @param string $ipAddress Usually the current HTTP requester's IP, retrieved from $_SERVER[REMOTE_ADDR]
     * @return ApiToken
     */
    public static function retrieveOrCreateToken(Model $user, $ipAddress)
    {
        try {
            $token = self::findFirst(new AndGroup([
                new Equals("AuthenticatedUserID", $user->UniqueIdentifier),
                new Equals("IpAddress", $ipAddress),
                new GreaterThan("Expires", "now", true)
            ]));

            $token->Expires = self::TOKEN_EXPIRATION;
            $token->save();
        } catch (RecordNotFoundException $ex) {
            $token = self::createToken($user, $ipAddress);
        }

        return $token;
    }

    protected function createConsistencyValidator()
    {
        $validator = new Validator();
        $validator->validations[] = new HasValue("Token");

        return $validator;
    }

    protected function beforeSave()
    {
        if ($this->isNewRecord()) {
            $this->Expires = self::TOKEN_EXPIRATION;
        }

        parent::beforeSave();
    }
}
