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
namespace UserAccessManager\Wrapper;

use WP_CLI\Formatter;

/**
 * Class WordpressCli
 *
 * @package UserAccessManager\Wrapper
 */
class WordpressCli
{
    /**
     * @see \WP_CLI::success()
     *
     * @param string $message
     *
     * @return null
     */
    public function success($message)
    {
        return \WP_CLI::success($message);
    }

    /**
     * @see \WP_CLI::error()
     *
     * @param string|\WP_Error $message Message to write to STDERR.
     * @param bool|integer     $exit    True defaults to exit(1).
     *
     * @return null
     */
    public function error($message, $exit = true)
    {
        return \WP_CLI::error($message, $exit);
    }

    /**
     * @see \WP_CLI::line()
     *
     * @param string $message
     *
     * @return null
     */
    public function line($message = '')
    {
        return \WP_CLI::line($message);
    }

    /**
     * @see Formatter
     *
     * @param array       $assocArguments
     * @param array       $fields
     * @param bool|string $prefix
     *
     * @return Formatter
     */
    public function createFormatter(array &$assocArguments, array $fields = null, $prefix = false)
    {
        return new Formatter($assocArguments, $fields, $prefix);
    }
}
