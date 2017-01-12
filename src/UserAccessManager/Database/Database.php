<?php

namespace UserAccessManager\Database;

use UserAccessManager\Wrapper\Wordpress;

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
    protected $_oWrapper;

    /**
     * Database constructor.
     *
     * @param Wordpress $oWrapper
     */
    public function __construct(Wordpress $oWrapper)
    {
        $this->_oWrapper = $oWrapper;
        $this->_oWpDatabase = $oWrapper->getDatabase();
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
     * @see dbDelta()
     *
     * @param string $mQueries
     * @param bool   $blExecute
     *
     * @return array
     */
    public function dbDelta($mQueries = '', $blExecute = true)
    {
        return $this->_oWrapper->dbDelta($mQueries, $blExecute);
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
}