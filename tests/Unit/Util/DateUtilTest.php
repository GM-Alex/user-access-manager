<?php
/**
 * DateUtilTest.php
 *
 * The DateUtilTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Util;

use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Util\DateUtil;

/**
 * Class DateUtilTest
 *
 * @package UserAccessManager\Tests\Unit\Util
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
     */
    public function testFormatDateForDateInput()
    {
        $dateUtil = new DateUtil($this->getWordpress());

        self::assertEquals(null, $dateUtil->formatDateForDatetimeInput(null));
        self::assertEquals('1970-01-01', $dateUtil->formatDateForDateInput(0));
    }

    /**
     * @group  unit
     * @covers ::formatDateForTimeInput()
     */
    public function testFormatDateForTimeInput()
    {
        $dateUtil = new DateUtil($this->getWordpress());

        self::assertEquals(null, $dateUtil->formatDateForDatetimeInput(null));
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
