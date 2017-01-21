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
    const USER_OBJECT_TYPE = 'user';
    const POST_OBJECT_TYPE = 'post';
    const PAGE_OBJECT_TYPE = 'page';
    const TERM_OBJECT_TYPE = 'term';
    const ROLE_OBJECT_TYPE = 'role';
    const ATTACHMENT_OBJECT_TYPE = 'attachment';

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
    protected $_aTermTreeMap = null;

    /**
     * @var array
     */
    protected $_aPostTreeMap = null;

    /**
     * @var array
     */
    protected $_aPlObjects = array();

    /**
     * @var array
     */
    protected $_aObjectTypes = null;

    /**
     * @var array
     */
    protected $_aPostableTypes = null;

    /**
     * @var array
     */
    protected $_aAllObjectTypes = null;

    /**
     * @var array
     */
    protected $_aAllObjectTypesMap = null;

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
            $this->_aPostTypes = $this->_oWrapper->getPostTypes(array('publicly_queryable' => true));
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
            $this->_aTaxonomies = $this->_oWrapper->getTaxonomies();
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
     * @return \WP_Post|array|null
     */
    public function getPost($sId)
    {
        if (!isset($this->_aPosts[$sId])) {
            $this->_aPosts[$sId] = $this->_oWrapper->getPost($sId);
        }

        return $this->_aPosts[$sId];
    }

    /**
     * Returns a term.
     *
     * @param string $sId       The term id.
     * @param string $sTaxonomy The taxonomy.
     *
     * @return mixed
     */
    public function getTerm($sId, $sTaxonomy = '')
    {
        if (!isset($this->_aTerms[$sId])) {
            $this->_aTerms[$sId] = $this->_oWrapper->getTerm($sId, $sTaxonomy);
        }

        return $this->_aTerms[$sId];
    }

    /**
     * Resolves all sub elements
     *
     * @param array  $aTree
     * @param string $iId
     *
     * @return array
     */
    protected function _processTreeMapSiblings(&$aTree, $iId)
    {
        foreach ($aTree[$iId] as $iChildId => $sType) {
            if (isset($aTree[$iChildId])) {
                $aSiblings = $this->_processTreeMapSiblings($aTree, $iChildId);
                $aTree[$iId] = $aTree[$iId] + $aSiblings;
            }
        }

        return $aTree[$iId];
    }

    /**
     * Returns the tree map for the query.
     *
     * @param string $sSelect
     *
     * @return array
     */
    protected function _getTreeMap($sSelect)
    {
        $aTree = array();
        $aResults = $this->_oDatabase->getResults($sSelect);

        foreach ($aResults as $oResult) {
            if (!isset($aTree[$oResult->parentId])) {
                $aTree[$oResult->parentId] = array();
            }

            $aTree[$oResult->parentId][$oResult->id] = $oResult->type;
        }

        //Add siblings
        foreach ($aTree as $iParentId => $aChildren) {
            $this->_processTreeMapSiblings($aTree, $iParentId);
        }

        return $aTree;
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

            $this->_aPostTreeMap = $this->_getTreeMap($sSelect);
        }

        return $this->_aPostTreeMap;
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
                SELECT tr.object_id, tr.term_taxonomy_id, p.post_type
                FROM {$this->_oDatabase->getTermRelationshipsTable()} AS tr 
                  LEFT JOIN {$this->_oDatabase->getPostsTable()} as p
                  ON (tr.object_id = p.ID)";

            $aResults = $this->_oDatabase->getResults($sSelect);

            foreach ($aResults as $oResult) {
                if (!isset($this->_aTermPostMap[$oResult->term_taxonomy_id])) {
                    $this->_aTermPostMap[$oResult->term_taxonomy_id] = array();
                }

                $this->_aTermPostMap[$oResult->term_taxonomy_id][$oResult->object_id] = $oResult->post_type;
            }
        }

        return $this->_aTermPostMap;
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
                SELECT term_id AS id, parent AS parentId, taxonomy as type
                FROM {$this->_oDatabase->getTermTaxonomyTable()}
                  WHERE parent != 0";

            $this->_aTermTreeMap = $this->_getTreeMap($sSelect);
        }

        return $this->_aTermTreeMap;
    }

    /**
     * used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string    $sPostType  The string for the new post_type
     * @param \stdClass $oArguments The array of arguments used to create the post_type
     *
     */
    public function registeredPostType($sPostType, $oArguments)
    {
        if ($oArguments->publicly_queryable) {
            $this->_aPostableTypes = $this->getPostableTypes();
            $this->_aPostableTypes[$oArguments->name] = $oArguments->name;
            $this->_aObjectTypes = null;
            $this->_aAllObjectTypes = null;
            $this->_aAllObjectTypesMap = null;
            $this->_aValidObjectTypes = null;
        }
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
            $aObjectTypesMap = $this->getAllObjectTypesMap();

            if (isset($aObjectTypesMap[$sObjectType])) {
                $this->_aValidObjectTypes[$sObjectType] = true;
            } else {
                $this->_aValidObjectTypes[$sObjectType] = false;
            }
        }

        return $this->_aValidObjectTypes[$sObjectType];
    }

    /**
     * Checks if type is postable.
     *
     * @param string $sType
     *
     * @return bool
     */
    public function isPostableType($sType)
    {
        $aPostableTypes = $this->getPostableTypes();
        return isset($aPostableTypes[$sType]);
    }

    /**
     * Returns the predefined object types.
     *
     * @return array;
     */
    public function getPostableTypes()
    {
        if ($this->_aPostableTypes === null) {
            $aStaticPostableTypes = array(
                self::POST_OBJECT_TYPE => self::POST_OBJECT_TYPE,
                self::PAGE_OBJECT_TYPE => self::PAGE_OBJECT_TYPE,
                self::ATTACHMENT_OBJECT_TYPE => self::ATTACHMENT_OBJECT_TYPE
            );
            $this->_aPostableTypes = array_merge($aStaticPostableTypes, $this->getPostTypes());
        }

        return $this->_aPostableTypes;
    }

    /**
     * Returns the predefined object types.
     *
     * @return array
     */
    public function getObjectTypes()
    {
        if ($this->_aObjectTypes === null) {
            $aStaticObjectTypes = array(
                self::TERM_OBJECT_TYPE => self::TERM_OBJECT_TYPE,
                self::USER_OBJECT_TYPE => self::USER_OBJECT_TYPE,
                self::ROLE_OBJECT_TYPE => self::ROLE_OBJECT_TYPE
            );

            $this->_aObjectTypes = array_merge(
                $this->getPostableTypes(),
                $aStaticObjectTypes,
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
            $aPlObjects = $this->getPlObjects();

            $this->_aAllObjectTypes = array_merge(
                $aObjectTypes,
                array_keys($aPlObjects)
            );
        }

        return $this->_aAllObjectTypes;
    }

    /**
     * Returns all objects types as map.
     *
     * @return array
     */
    public function getAllObjectTypesMap()
    {
        if ($this->_aAllObjectTypesMap === null) {
            $this->_aAllObjectTypesMap = array_flip($this->getAllObjectTypes());
        }

        return $this->_aAllObjectTypesMap;
    }

    /**
     * Registers object that should be handel by the user access manager.
     *
     * @param array $oObject The object which you want to register.
     *
     * @return boolean
     */
    public function registerPlObject($oObject)
    {
        if (!isset($oObject['name'])
            || !isset($oObject['reference'])
            || !isset($oObject['getFull'])
            || !isset($oObject['getFullObjects'])
        ) {
            return false;
        }

        $this->_aPlObjects[$oObject['name']] = $oObject;

        return true;
    }

    /**
     * Returns a registered pluggable object.
     *
     * @param string $sObjectName The name of the object which should be returned.
     *
     * @return array
     */
    public function getPlObject($sObjectName)
    {
        if (isset($this->_aPlObjects[$sObjectName])) {
            return $this->_aPlObjects[$sObjectName];
        }

        return array();
    }

    /**
     * Returns all registered pluggable objects.
     *
     * @return array
     */
    public function getPlObjects()
    {
        return $this->_aPlObjects;
    }
}