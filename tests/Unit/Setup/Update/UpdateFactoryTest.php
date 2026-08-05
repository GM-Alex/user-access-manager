<?php

namespace UserAccessManager\Tests\Unit\Setup\Update;

use UserAccessManager\Setup\Update\DatabaseUpdate1;
use UserAccessManager\Setup\Update\DatabaseUpdate2;
use UserAccessManager\Setup\Update\DatabaseUpdate3;
use UserAccessManager\Setup\Update\DatabaseUpdate4;
use UserAccessManager\Setup\Update\DatabaseUpdate5;
use UserAccessManager\Setup\Update\DatabaseUpdate6;
use UserAccessManager\Setup\Update\DatabaseUpdate7;
use UserAccessManager\Setup\Update\DatabaseUpdate8;
use UserAccessManager\Setup\Update\UpdateFactory;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Setup\Update\UpdateFactory
 */
class UpdateFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @return UpdateFactory
     */
    public function testCanCreateInstance(): UpdateFactory
    {
        $updateFactory = new UpdateFactory(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(UpdateFactory::class, $updateFactory);

        return $updateFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getDatabaseUpdates()
     * @param UpdateFactory $updateFactory
     */
    public function testGetDatabaseUpdates(UpdateFactory $updateFactory)
    {
        $updates = $updateFactory->getDatabaseUpdates();

        self::assertInstanceOf(DatabaseUpdate1::class, $updates[0]);
        self::assertInstanceOf(DatabaseUpdate2::class, $updates[1]);
        self::assertInstanceOf(DatabaseUpdate3::class, $updates[2]);
        self::assertInstanceOf(DatabaseUpdate4::class, $updates[3]);
        self::assertInstanceOf(DatabaseUpdate5::class, $updates[4]);
        self::assertInstanceOf(DatabaseUpdate6::class, $updates[5]);
        self::assertInstanceOf(DatabaseUpdate7::class, $updates[6]);
        self::assertInstanceOf(DatabaseUpdate8::class, $updates[7]);
    }
}
