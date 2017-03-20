<?php
/**
 * Database.php
 *
 * The Database class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Database;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Database
 *
 * @package UserAccessManager\Database
 */
class Database
{
    CONST USER_GROUP_TABLE_NAME = 'uam_accessgroups';
    CONST USER_GROUP_TO_OBJECT_TABLE_NAME = 'uam_accessgroup_to_object';

    /**
     * @var \wpdb
     */
    protected $_oWpDatabase;

    /**
     * @var Database
     */
    protected $_oWordpress;

    /**
     * Database constructor.
     *
     * @param Wordpress $oWordpress
     */
    public function __construct(Wordpress $oWordpress)
    {
        $this->_oWordpress = $oWordpress;
        $this->_oWpDatabase = $oWordpress->getDatabase();
    }

    /**
     * Returns the user group table name.
     *
     * @return string
     */
    public function getUserGroupTable()
    {
        return $this->_oWpDatabase->prefix.self::USER_GROUP_TABLE_NAME;
    }

    /**
     * Returns the user group table name.
     *
     * @return string
     */
    public function getUserGroupToObjectTable()
    {
        return $this->_oWpDatabase->prefix.self::USER_GROUP_TO_OBJECT_TABLE_NAME;
    }

    /**
     * Returns a id list for sql.
     *
     * @param array $aIds
     *
     * @return string
     */
    public function generateSqlIdList(array $aIds)
    {
        return (count($aIds) > 0) ? implode(', ', $aIds) : '\'\'';
    }

    /**
     * @see dbDelta()
     *
     * @param string $mQueries
     * @param bool   $blExecute
     *
     * @return array
     */
    public function dbDelta($mQueries = '', $blExecute = true)
    {
        return $this->_oWordpress->dbDelta($mQueries, $blExecute);
    }

    /**
     * @see \wpdb::$prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_oWpDatabase->prefix;
    }

    /**
     * Returns the last insert id.
     *
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->_oWpDatabase->insert_id;
    }

    /**
     * Returns the current blog id.
     *
     * @return int
     */
    public function getCurrentBlogId()
    {
        return $this->_oWpDatabase->blogid;
    }

    /**
     * Returns the blogs table name.
     *
     * @return string
     */
    public function getBlogsTable()
    {
        return $this->_oWpDatabase->blogs;
    }

    /**
     * Returns the posts table name.
     *
     * @return string
     */
    public function getPostsTable()
    {
        return $this->_oWpDatabase->posts;
    }

    /**
     * Returns the term_relationships table name.
     *
     * @return string
     */
    public function getTermRelationshipsTable()
    {
        return $this->_oWpDatabase->term_relationships;
    }

    /**
     * Returns the term_taxonomy table name.
     *
     * @return string
     */
    public function getTermTaxonomyTable()
    {
        return $this->_oWpDatabase->term_taxonomy;
    }

    /**
     * Returns the users table name.
     *
     * @return string
     */
    public function getUsersTable()
    {
        return $this->_oWpDatabase->users;
    }

    /**
     * Returns the capabilities table name.
     *
     * @return string
     */
    public function getCapabilitiesTable()
    {
        return $this->_oWpDatabase->prefix.'capabilities';
    }

    /**
     * @see \wpdb::get_col()
     *
     * @param string $sQuery
     * @param int    $iColumn
     *
     * @return array
     */
    public function getColumn($sQuery = null, $iColumn = 0)
    {
        return $this->_oWpDatabase->get_col($sQuery, $iColumn);
    }

    /**
     * @see \wpdb::get_row()
     *
     * @param string $sQuery
     * @param string $sOutput
     * @param int    $iRow
     *
     * @return array|null|object
     */
    public function getRow($sQuery = null, $sOutput = OBJECT, $iRow = 0)
    {
        return $this->_oWpDatabase->get_row($sQuery, $sOutput, $iRow);
    }

    /**
     * @see \wpdb::get_var()
     *
     * @param null $sQuery
     * @param int  $iColumn
     * @param int  $iRow
     *
     * @return null|string
     */
    public function getVariable($sQuery = null, $iColumn = 0, $iRow = 0)
    {
        return $this->_oWpDatabase->get_var($sQuery, $iColumn, $iRow);
    }

    /**
     * @see \wpdb::get_blog_prefix()
     *
     * @param int $iBlogId
     *
     * @return string
     */
    public function getBlogPrefix($iBlogId = null)
    {
        return $this->_oWpDatabase->get_blog_prefix($iBlogId);
    }

    /**
     * @see \wpdb::prepare()
     *
     * @param string $sQuery
     * @param mixed  $mArguments
     *
     * @return string
     */
    public function prepare($sQuery, $mArguments)
    {
        return $this->_oWpDatabase->prepare($sQuery, $mArguments);
    }

    /**
     * @see \wpdb::query()
     *
     * @param string $sQuery
     *
     * @return false|int
     */
    public function query($sQuery)
    {
        return $this->_oWpDatabase->query($sQuery);
    }

    /**
     * @see \wpdb::get_results()
     *
     * @param null   $sQuery
     * @param string $sOutput
     *
     * @return array|null|object
     */
    public function getResults($sQuery = null, $sOutput = OBJECT)
    {
        return $this->_oWpDatabase->get_results($sQuery, $sOutput);
    }

    /**
     * @see \wpdb::insert()
     *
     * @param string       $sTable
     * @param array        $aData
     * @param array|string $sFormat
     *
     * @return false|int
     */
    public function insert($sTable, array $aData, $sFormat = null)
    {
        return $this->_oWpDatabase->insert($sTable, $aData, $sFormat);
    }

    /**
     * @see \wpdb::update()
     *
     * @param string       $sTable
     * @param array        $aData
     * @param array        $aWhere
     * @param array|string $mFormat
     * @param array|string $mWhereFormat
     *
     * @return false|int
     */
    public function update($sTable, array $aData, array $aWhere, $mFormat = null, $mWhereFormat = null)
    {
        return $this->_oWpDatabase->update($sTable, $aData, $aWhere, $mFormat, $mWhereFormat);
    }

    /**
     * @see \wpdb::delete()
     *
     * @param string       $sTable
     * @param array        $aWhere
     * @param array|string $mWhereFormat
     *
     * @return false|int
     */
    public function delete($sTable, array $aWhere, $mWhereFormat = null)
    {
        return $this->_oWpDatabase->delete($sTable, $aWhere, $mWhereFormat);
    }

    /**
     * Returns the database charset.
     *
     * @return string
     */
    public function getCharset()
    {
        $sCharsetCollate = '';

        $sMySlqVersion = $this->getVariable('SELECT VERSION() as mysql_version');

        if (version_compare($sMySlqVersion, '4.1.0', '>=')) {
            if (!empty($this->_oWpDatabase->charset)) {
                $sCharsetCollate = "DEFAULT CHARACTER SET {$this->_oWpDatabase->charset}";
            }

            if (!empty($this->_oWpDatabase->collate)) {
                $sCharsetCollate .= " COLLATE {$this->_oWpDatabase->collate}";
            }
        }

        return $sCharsetCollate;
    }

}