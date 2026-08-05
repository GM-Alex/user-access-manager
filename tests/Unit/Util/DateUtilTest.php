<?php

namespace UserAccessManager\Tests\Unit\Util;

use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Util\DateUtil;

/**
 * @coversDefaultClass \UserAccessManager\Util\DateUtil
 */
class DateUtilTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $dateUtil = new DateUtil($this->getWordpress());

        self::assertInstanceOf(DateUtil::class, $dateUtil);
    }

    /**
     * @group  unit
     * @covers ::formatDate()
     */
    public function testFormatDate()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('formatDate')
            ->with('date')
            ->will($this->returnValue('formattedDate'));

        $dateUtil = new DateUtil($wordpress);

        self::assertEquals('formattedDate', $dateUtil->formatDate('date'));
    }

    /**
     * @group  unit
     * @covers ::formatDateForDatetimeInput()
     * @covers ::formatDateWith()
     */
    public function testFormatDateForDatetimeInput()
    {
        $dateUtil = new DateUtil($this->getWordpress());

        self::assertEquals(null, $dateUtil->formatDateForDatetimeInput(null));
        self::assertEquals('1970-01-01T00:00:00', $dateUtil->formatDateForDatetimeInput(0));
    }

    /**
     * @group  unit
     * @covers ::formatDateForDateInput()
     * @covers ::formatDateWith()
     */
    public function testFormatDateForDateInput()
    {
        $dateUtil = new DateUtil($this->getWordpress());

        self::assertEquals(null, $dateUtil->formatDateForDateInput(null));
        self::assertEquals('1970-01-01', $dateUtil->formatDateForDateInput(0));
    }

    /**
     * @group  unit
     * @covers ::formatDateForTimeInput()
     * @covers ::formatDateWith()
     */
    public function testFormatDateForTimeInput()
    {
        $dateUtil = new DateUtil($this->getWordpress());

        self::assertEquals(null, $dateUtil->formatDateForTimeInput(null));
        self::assertEquals('00:00:00', $dateUtil->formatDateForTimeInput(0));
    }

    /**
     * @group  unit
     * @covers ::getDateFromTime()
     */
    public function testGetDateFromTime()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('currentTime')
            ->with('timestamp')
            ->will($this->returnValue(100));

        $dateUtil = new DateUtil($wordpress);

        self::assertEquals(null, $dateUtil->getDateFromTime(null));
        self::assertEquals(null, $dateUtil->getDateFromTime(0));
        self::assertEquals('1970-01-01 00:01:41', $dateUtil->getDateFromTime(1));
    }
}
