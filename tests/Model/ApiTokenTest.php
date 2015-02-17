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

namespace Rhubarb\Scaffolds\TokenBasedRestApi\Tests\Model;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\TokenBasedRestApi\Model\ApiToken;
use Rhubarb\Stem\Schema\SolutionSchema;

class ApiTokenTest extends RhubarbTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        SolutionSchema::registerSchema("Authentication",
            "\Rhubarb\Scaffolds\Authentication\DatabaseSchema");
        SolutionSchema::registerSchema("TokenBasedRestApi",
            "\Rhubarb\Scaffolds\TokenBasedRestApi\Model\TokenBasedRestApiSolutionSchema");
        SolutionSchema::registerSchema("ApiTokenTest",
            "\Rhubarb\Scaffolds\TokenBasedRestApi\Tests\Model\UnitTestTokenBaseRestApiSolutionSchema");
    }

    public function testTokenGetsExpiry()
    {
        $token = new ApiToken();
        $token->Token = "abc123";
        $token->Save();

        $this->assertInstanceOf("Rhubarb\Crown\DateTime\RhubarbDateTime", $token->Expires);
        $this->assertGreaterThanOrEqual(new RhubarbDateTime("+1 day"), $token->Expires);
    }

    public function testTokenCreation()
    {
        $user = new User();
        $user->Username = "billy";
        $user->Forename = "bob";
        $user->Save();

        $token = ApiToken::createToken($user, "127.0.0.5");

        $this->assertEquals("127.0.0.5", $token->IpAddress);
        $this->assertGreaterThan(20, strlen($token->Token));
    }

    public function testTokenCanBeValidated()
    {
        $user = new User();
        $user->Username = "billy2";
        $user->Forename = "bob2";
        $user->Save();

        $token = ApiToken::createToken($user, "127.0.0.5");

        $returnedUser = ApiToken::validateToken($token->Token);

        $this->assertEquals($user->UniqueIdentifier, $returnedUser->UniqueIdentifier,
            "ApiToken isn't validating tokens");
    }
}

class UnitTestTokenBaseRestApiSolutionSchema extends SolutionSchema
{
    public function __construct($version = 0.1)
    {
        parent::__construct($version);
    }

    protected function defineRelationships()
    {
        parent::defineRelationships();

        $this->DeclareOneToManyRelationships(
            [
                "User" =>
                    [
                        "Tokens" => "ApiToken.AuthenticatedUserID:AuthenticatedUser"
                    ]
            ]);
    }
}