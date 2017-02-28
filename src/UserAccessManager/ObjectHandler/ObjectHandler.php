<?php
/**
 * ObjectHandler.php
 *
 * The ObjectHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\ObjectHandler;

use UserAccessManager\Database\Database;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ObjectHandler
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandler
{
    const TREE_MAP_PARENTS = 'PARENT';
    const TREE_MAP_CHILDREN = 'CHILDREN';
    const GENERAL_ROLE_OBJECT_TYPE = '_role_';
    const GENERAL_USER_OBJECT_TYPE = '_user_';
    const GENERAL_POST_OBJECT_TYPE = '_post_';
    const GENERAL_TERM_OBJECT_TYPE = '_term_';
    const ATTACHMENT_OBJECT_TYPE = 'attachment';
    const POST_OBJECT_TYPE = 'post';
    const PAGE_OBJECT_TYPE = 'page';
    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var array
     */
    protected $_aPostTypes = null;

    /**
     * @var array
     */
    protected $_aTaxonomies = null;

    /**
     * @var \WP_User
     */
    protected $_aUsers = null;

    /**
     * @var \WP_Post[]
     */
    protected $_aPosts = null;

    /**
     * @var \WP_Term[]
     */
    protected $_aTerms = null;

    /**
     * @var array
     */
    protected $_aTermPostMap = null;

    /**
     * @var array
     */
    protected $_aPostTermMap = null;

    /**
     * @var array
     */
    protected $_aTermTreeMap = null;

    /**
     * @var array
     */
    protected $_aPostTreeMap = null;

    /**
     * @var array
     */
    protected $_aPluggableObjects = array();

    /**
     * @var array
     */
    protected $_aObjectTypes = null;

    /**
     * @var array
     */
    protected $_aAllObjectTypes = null;

    /**
     * @var array
     */
    protected $_aValidObjectTypes = array();

    /**
     * Cache constructor.
     *
     * @param Wordpress $oWrapper
     * @param Database  $oDatabase
     */
    public function __construct(Wordpress $oWrapper, Database $oDatabase)
    {
        $this->_oWrapper = $oWrapper;
        $this->_oDatabase = $oDatabase;
    }

    /**
     * Returns all post types.
     *
     * @return array
     */
    public function getPostTypes()
    {
        if ($this->_aPostTypes === null) {
            $this->_aPostTypes = $this->_oWrapper->getPostTypes(array('public' => true));
        }

        return $this->_aPostTypes;
    }

    /**
     * Returns the taxonomies.
     *
     * @return array
     */
    public function getTaxonomies()
    {
        if ($this->_aTaxonomies === null) {
            $this->_aTaxonomies = $this->_oWrapper->getTaxonomies(array('public' => true));
        }

        return $this->_aTaxonomies;
    }

    /**
     * Returns a user.
     *
     * @param int|string $sId The user id.
     *
     * @return \WP_User|false
     */
    public function getUser($sId)
    {
        if (!isset($this->_aUsers[$sId])) {
            $this->_aUsers[$sId] = $this->_oWrapper->getUserData($sId);
        }

        return $this->_aUsers[$sId];
    }

    /**
     * Returns a post.
     *
     * @param string $sId The post id.
     *
     * @return \WP_Post|array|false
     */
    public function getPost($sId)
    {
        if (!isset($this->_aPosts[$sId])) {
            $oPost = $this->_oWrapper->getPost($sId);
            $this->_aPosts[$sId] = ($oPost === null) ? false : $oPost;
        }

        return $this->_aPosts[$sId];
    }

    /**
     * Returns a term.
     *
     * @param string $sId       The term id.
     * @param string $sTaxonomy The taxonomy.
     *
     * @return array|false|\WP_Error|\WP_Term
     */
    public function getTerm($sId, $sTaxonomy = '')
    {
        $sFullId = $sId.'|'.$sTaxonomy;

        if (!isset($this->_aTerms[$sFullId])) {
            $oTerm = $this->_oWrapper->getTerm($sId, $sTaxonomy);
            $this->_aTerms[$sFullId] = ($oTerm === null) ? false : $oTerm;
        }

        return $this->_aTerms[$sFullId];
    }

    /**
     * Resolves all tree map elements
     *
     * @param array $aMap
     * @param array $aSubMap
     *
     * @return array
     */
    protected function _processTreeMapElements(array &$aMap, array $aSubMap = null)
    {
        $aProcessMap = ($aSubMap === null) ? $aMap : $aSubMap;

        foreach ($aProcessMap as $iId => $aSubIds) {
            foreach ($aSubIds as $iSubId) {
                if (isset($aMap[$iSubId])) {
                    $aMap[$iId] += $this->_processTreeMapElements($aMap, array($iSubId => $aMap[$iSubId]))[$iSubId];
                }
            }
        }

        return $aMap;
    }

    /**
     * Returns the tree map for the query.
     *
     * @param string $sSelect
     * @param string $sGeneralType
     *
     * @return array
     */
    protected function _getTreeMap($sSelect, $sGeneralType)
    {
        $aTreeMap = array(
            self::TREE_MAP_CHILDREN => array(
                $sGeneralType => array()
            ),
            self::TREE_MAP_PARENTS => array(
                $sGeneralType => array()
            )
        );
        $aResults = $this->_oDatabase->getResults($sSelect);

        foreach ($aResults as $oResult) {
            if (!isset($aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type])) {
                $aChildrenMap[$oResult->type] = array();
            }

            if (!isset($aTreeMap[self::TREE_MAP_PARENTS][$oResult->type])) {
                $aTreeMap[self::TREE_MAP_PARENTS][$oResult->type] = array();
            }

            if (!isset($aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type][$oResult->parentId])) {
                $aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type][$oResult->parentId] = array();
            }

            if (!isset($aTreeMap[self::TREE_MAP_PARENTS][$oResult->type][$oResult->id])) {
                $aTreeMap[self::TREE_MAP_PARENTS][$oResult->type][$oResult->id] = array();
            }

            $aTreeMap[self::TREE_MAP_CHILDREN][$sGeneralType][$oResult->parentId][$oResult->id] = $oResult->id;
            $aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type][$oResult->parentId][$oResult->id] = $oResult->id;
            $aTreeMap[self::TREE_MAP_PARENTS][$sGeneralType][$oResult->id][$oResult->parentId] = $oResult->parentId;
            $aTreeMap[self::TREE_MAP_PARENTS][$oResult->type][$oResult->id][$oResult->parentId] = $oResult->parentId;
        }

        //Process elements
        foreach ($aTreeMap as $sMapType => $aMayTypeMap) {
            foreach ($aMayTypeMap as $sObjectType => $aMap) {
                $aTreeMap[$sMapType][$sObjectType] = $this->_processTreeMapElements($aMap);
            }
        }

        return $aTreeMap;
    }

    /**
     * Returns the post tree map.
     *
     * @return array
     */
    public function getPostTreeMap()
    {
        if ($this->_aPostTreeMap === null) {
            $sSelect = "
                SELECT ID AS id, post_parent AS parentId, post_type AS type 
                FROM {$this->_oDatabase->getPostsTable()}
                  WHERE post_parent != 0";

            $this->_aPostTreeMap = $this->_getTreeMap($sSelect, self::GENERAL_POST_OBJECT_TYPE);
        }

        return $this->_aPostTreeMap;
    }

    /**
     * Returns the term tree map.
     *
     * @return array
     */
    public function getTermTreeMap()
    {
        if ($this->_aTermTreeMap === null) {
            $sSelect = "
                SELECT term_id AS id, parent AS parentId, taxonomy AS type
                FROM {$this->_oDatabase->getTermTaxonomyTable()}
                  WHERE parent != 0";

            $this->_aTermTreeMap = $this->_getTreeMap($sSelect, self::GENERAL_TERM_OBJECT_TYPE);
        }

        return $this->_aTermTreeMap;
    }

    /**
     * Returns the term post map.
     *
     * @return array
     */
    public function getTermPostMap()
    {
        if ($this->_aTermPostMap === null) {
            $this->_aTermPostMap = array();

            $sSelect = "
                SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                FROM {$this->_oDatabase->getTermRelationshipsTable()} AS tr 
                  LEFT JOIN {$this->_oDatabase->getPostsTable()} AS p ON (tr.object_id = p.ID)
                  LEFT JOIN {$this->_oDatabase->getTermTaxonomyTable()} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $aResults = $this->_oDatabase->getResults($sSelect);

            foreach ($aResults as $oResult) {
                if (!isset($this->_aTermPostMap[$oResult->termId])) {
                    $this->_aTermPostMap[$oResult->termId] = array();
                }

                $this->_aTermPostMap[$oResult->termId][$oResult->objectId] = $oResult->objectId;
            }
        }

        return $this->_aTermPostMap;
    }

    /**
     * Returns the post term map.
     *
     * @return array
     */
    public function getPostTermMap()
    {
        if ($this->_aPostTermMap === null) {
            $this->_aPostTermMap = array();
            $aTermPostMap = $this->getTermPostMap();

            foreach ($aTermPostMap as $iTermId => $aPosts) {
                foreach ($aPosts as $iPostId => $sPostType) {
                    if (!isset($this->_aPostTermMap[$iPostId])) {
                        $this->_aPostTermMap[$iPostId] = array();
                    }

                    $this->_aPostTermMap[$iPostId][$iTermId] = $iTermId;
                }
            }

        }

        return $this->_aPostTermMap;
    }

    /**
     * Used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string        $sPostType  The string for the new post_type
     * @param \WP_Post_Type $oArguments The array of arguments used to create the post_type
     */
    public function registeredPostType($sPostType, \WP_Post_Type $oArguments)
    {
        if ((bool)$oArguments->public === true) {
            $this->_aPostTypes = $this->getPostTypes();
            $this->_aPostTypes[$sPostType] = $sPostType;
            $this->_aObjectTypes = null;
            $this->_aAllObjectTypes = null;
            $this->_aValidObjectTypes = null;
        }
    }

    /**
     * Adds an custom taxonomy.
     *
     * @param string $sTaxonomy
     * @param string $sObjectType
     * @param array  $aArguments
     */
    public function registeredTaxonomy($sTaxonomy, $sObjectType, array $aArguments)
    {
        if ((bool)$aArguments['public'] === true) {
            $this->_aTaxonomies = $this->getTaxonomies();
            $this->_aTaxonomies[$sTaxonomy] = $sTaxonomy;
            $this->_aObjectTypes = null;
            $this->_aAllObjectTypes = null;
            $this->_aValidObjectTypes = null;
        }
    }

    /**
     * Checks if type is postable.
     *
     * @param string $sType
     *
     * @return bool
     */
    public function isPostType($sType)
    {
        $aPostableTypes = $this->getPostTypes();
        return isset($aPostableTypes[$sType]);
    }

    /**
     * Checks if the taxonomy is a valid one.
     *
     * @param string $sTaxonomy
     *
     * @return bool
     */
    public function isTaxonomy($sTaxonomy)
    {
        $aTaxonomies = $this->getTaxonomies();
        return in_array($sTaxonomy, $aTaxonomies);
    }

    /**
     * Registers object that should be handel by the user access manager.
     *
     * @param PluggableObject $oObject The object which you want to register.
     */
    public function registerPluggableObject(PluggableObject $oObject)
    {
        $this->_aPluggableObjects[$oObject->getName()] = $oObject;
    }

    /**
     * Returns a registered pluggable object.
     *
     * @param string $sObjectName The name of the object which should be returned.
     *
     * @return PluggableObject
     */
    public function getPluggableObject($sObjectName)
    {
        if (isset($this->_aPluggableObjects[$sObjectName])) {
            return $this->_aPluggableObjects[$sObjectName];
        }

        return null;
    }

    /**
     * Returns true if the object is a pluggable object.
     *
     * @param string $sObjectName
     *
     * @return bool
     */
    public function isPluggableObject($sObjectName)
    {
        return isset($this->_aPluggableObjects[$sObjectName]);
    }

    /**
     * Returns all registered pluggable objects.
     *
     * @return PluggableObject[]
     */
    public function getPluggableObjects()
    {
        return $this->_aPluggableObjects;
    }

    /**
     * Returns the predefined object types.
     *
     * @return array
     */
    public function getObjectTypes()
    {
        if ($this->_aObjectTypes === null) {
            $this->_aObjectTypes = array_merge(
                $this->getPostTypes(),
                $this->getTaxonomies()
            );
        }

        return $this->_aObjectTypes;
    }

    /**
     * Returns all objects types.
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        if ($this->_aAllObjectTypes === null) {
            $aObjectTypes = $this->getObjectTypes();
            $aPluggableObjects = $this->getPluggableObjects();
            $aPluggableObjectKeys = array_keys($aPluggableObjects);
            $aPluggableObjectKeys = array_combine($aPluggableObjectKeys, $aPluggableObjectKeys);

            $this->_aAllObjectTypes = array_merge(
                [
                    self::GENERAL_ROLE_OBJECT_TYPE => self::GENERAL_ROLE_OBJECT_TYPE,
                    self::GENERAL_USER_OBJECT_TYPE => self::GENERAL_USER_OBJECT_TYPE,
                    self::GENERAL_POST_OBJECT_TYPE => self::GENERAL_POST_OBJECT_TYPE,
                    self::GENERAL_TERM_OBJECT_TYPE => self::GENERAL_TERM_OBJECT_TYPE
                ],
                $aObjectTypes,
                $aPluggableObjectKeys
            );
        }

        return $this->_aAllObjectTypes;
    }

    /**
     * Returns the general object type.
     *
     * @param string $sObjectType
     *
     * @return string
     */
    public function getGeneralObjectType($sObjectType)
    {
        if ($sObjectType === self::GENERAL_USER_OBJECT_TYPE
            ||$sObjectType === self::GENERAL_ROLE_OBJECT_TYPE
        ) {
            return $sObjectType;
        } elseif ($this->isPostType($sObjectType)) {
            return self::GENERAL_POST_OBJECT_TYPE;
        } elseif ($this->isTaxonomy($sObjectType)) {
            return self::GENERAL_TERM_OBJECT_TYPE;
        }

        return null;
    }

    /**
     * Checks if the object type is a valid one.
     *
     * @param string $sObjectType The object type to check.
     *
     * @return boolean
     */
    public function isValidObjectType($sObjectType)
    {
        if (!isset($this->_aValidObjectTypes[$sObjectType])) {
            $aObjectTypesMap = $this->getAllObjectTypes();
            $this->_aValidObjectTypes[$sObjectType] = isset($aObjectTypesMap[$sObjectType]);
        }

        return $this->_aValidObjectTypes[$sObjectType];
    }
}