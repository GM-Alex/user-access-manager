<?php

namespace UserAccessManager\Tests\Unit\Setup\Update;

use UserAccessManager\Setup\Update\DatabaseUpdate7;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate7
 */
class DatabaseUpdate7Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate7(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate7::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate7(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.6.2', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('query')
            ->with(
                new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable
                    MODIFY ID INT NOT NULL AUTO_INCREMENT'
                )
            )
            ->will($this->onConsecutiveCalls(false, 5));

        $update = new DatabaseUpdate7($database, $this->getObjectHandler());

        // query() returning false must yield false, any other result must yield true.
        self::assertFalse($update->update());
        self::assertTrue($update->update());
    }
}
