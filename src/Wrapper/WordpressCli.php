<?php
/**
 * WordpressCli.php
 *
 * The WordpressCli class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Wrapper;

use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI\Formatter;
use WP_Error;

/**
 * Class WordpressCli
 *
 * @package UserAccessManager\Wrapper
 */
class WordpressCli
{
    /**
     * @param string $message
     * @return null
     * @see \WP_CLI::success()
     */
    public function success(string $message)
    {
        return WP_CLI::success($message);
    }

    /**
     * @param string|WP_Error $message Message to write to STDERR.
     * @param bool|integer $exit True defaults to exit(1).
     * @return null
     * @throws ExitException
     * @see \WP_CLI::error()
     */
    public function error($message, $exit = true)
    {
        return WP_CLI::error($message, $exit);
    }

    /**
     * @param string $message
     * @return null
     * @see \WP_CLI::line()
     */
    public function line($message = '')
    {
        return WP_CLI::line($message);
    }

    /**
     * @param array $assocArguments
     * @param array|null $fields
     * @param bool|string $prefix
     * @return Formatter
     * @see Formatter
     */
    public function createFormatter(array &$assocArguments, array $fields = null, $prefix = false): Formatter
    {
        return new Formatter($assocArguments, $fields, $prefix);
    }
}
