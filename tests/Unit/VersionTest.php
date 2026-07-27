<?php
/**
 * VersionTest.php
 *
 * The VersionTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit;

use UserAccessManager\UserAccessManager;

/**
 * Class VersionTest
 *
 * Guards the single place the version is maintained, the plugin header.
 *
 * @package UserAccessManager\Tests\Unit
 */
class VersionTest extends UserAccessManagerTestCase
{
    private function getPluginRoot(): string
    {
        return __DIR__ . '/../..';
    }

    /**
     * @group unit
     */
    public function testTheVersionIsTakenFromThePluginHeader()
    {
        $pluginHeader = (string) file_get_contents($this->getPluginRoot() . '/user-access-manager.php');
        preg_match('/^[ \t\/*#@]*Version:(.*)$/mi', $pluginHeader, $matches);

        self::assertArrayHasKey(1, $matches, 'The plugin header must contain a version.');
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', trim($matches[1]));
        self::assertEquals(trim($matches[1]), UserAccessManager::VERSION);
    }

    /**
     * @group unit
     */
    public function testTheStableTagOfTheReadmeMatchesTheVersion()
    {
        $readme = (string) file_get_contents($this->getPluginRoot() . '/readme.txt');
        preg_match('/^Stable tag:(.*)$/mi', $readme, $matches);

        self::assertArrayHasKey(1, $matches, 'The readme must contain a stable tag.');
        self::assertEquals(
            UserAccessManager::VERSION,
            trim($matches[1]),
            'The stable tag is out of date, run grunt to take the plugin version over.'
        );
    }

    /**
     * @group unit
     */
    public function testTheVersionIsNotRepeatedInThePackageDefinitions()
    {
        foreach (['composer.json', 'package.json', 'package-lock.json'] as $packageDefinition) {
            $content = json_decode(
                (string) file_get_contents($this->getPluginRoot() . '/' . $packageDefinition),
                true
            );

            self::assertArrayNotHasKey(
                'version',
                $content,
                "$packageDefinition must not repeat the version, it belongs into the plugin header."
            );
        }
    }
}
