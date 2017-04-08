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
 * @version   SVN: $Id$
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
     * @param string $sMessage
     *
     * @return null
     */
    public function success($sMessage)
    {
        return \WP_CLI::success($sMessage);
    }

    /**
     * @see \WP_CLI::error()
     *
     * @param string|\WP_Error $mMessage Message to write to STDERR.
     * @param boolean|integer  $mExit    True defaults to exit(1).
     *
     * @return null
     */
    public function error($mMessage, $mExit = true)
    {
        return \WP_CLI::error($mMessage, $mExit);
    }

    /**
     * @see \WP_CLI::line()
     *
     * @param string $sMessage
     *
     * @return null
     */
    public function line($sMessage = '')
    {
        return \WP_CLI::line($sMessage);
    }

    /**
     * @see Formatter
     *
     * @param array       $aAssocArguments
     * @param array       $aFields
     * @param bool|string $mPrefix
     *
     * @return Formatter
     */
    public function createFormatter(array &$aAssocArguments, array $aFields = null, $mPrefix = false)
    {
        return new Formatter($aAssocArguments, $aFields, $mPrefix);
    }
}
