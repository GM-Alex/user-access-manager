<?php

declare(strict_types=1);

namespace UserAccessManager\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The version guards in the plugin file are the only thing a site below the supported
 * versions ever reaches, so they have to say the same as the declared requirements.
 * They drifted apart once: the guard still named 7.2 while the plugin required 8.0,
 * which let those sites run into an uncaught platform exception from the autoloader
 * instead of the notice the guard exists for.
 */
class PluginRequirementsTest extends TestCase
{
    private function getPluginFile(): string
    {
        return (string) file_get_contents(__DIR__ . '/../../user-access-manager.php');
    }

    private function getReadme(): string
    {
        return (string) file_get_contents(__DIR__ . '/../../readme.txt');
    }

    private function match(string $pattern, string $subject, string $description): string
    {
        self::assertSame(1, preg_match($pattern, $subject, $matches), "Could not read the $description");

        return $matches[1];
    }

    /**
     * @group unit
     */
    public function testThePhpGuardMatchesTheRequiredPhpVersion()
    {
        $pluginFile = $this->getPluginFile();

        $required = $this->match('/^ \* Requires PHP: *(\S+)$/m', $pluginFile, 'required php version');
        $guard = $this->match(
            '/version_compare\(\(string\)phpversion\(\), \'([^\']+)\'\)/',
            $pluginFile,
            'php version guard'
        );

        self::assertSame($required, $guard);
        self::assertSame(
            $required,
            $this->match('/^Requires PHP: *(\S+)$/m', $this->getReadme(), 'required php version of the readme')
        );
    }

    /**
     * @group unit
     */
    public function testTheWordpressGuardMatchesTheRequiredWordpressVersion()
    {
        $required = $this->match(
            '/^Requires at least: *(\S+)$/m',
            $this->getReadme(),
            'required wordpress version'
        );

        self::assertSame(
            $required,
            $this->match(
                '/version_compare\(\(string\)\$wp_version, \'([^\']+)\'\)/',
                $this->getPluginFile(),
                'wordpress version guard'
            )
        );
    }

    /**
     * A guard that passes for a version composer rejects would hand the site an uncaught
     * exception from the platform check instead of the admin notice.
     *
     * @group unit
     */
    public function testTheComposerConstraintMatchesTheRequiredPhpVersion()
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../../composer.json'), true);
        $required = $this->match('/^ \* Requires PHP: *(\S+)$/m', $this->getPluginFile(), 'required php version');

        self::assertSame('>=' . $required, $composer['require']['php']);
    }
}
