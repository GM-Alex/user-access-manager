<?php

namespace UserAccessManager\Cache;

use UserAccessManager\Wrapper\Wordpress;

class Cache
{
    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var array
     */
    protected $_aCache = array();

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
     * Cache constructor.
     *
     * @param Wordpress $oWrapper
     */
    public function __construct(Wordpress $oWrapper)
    {
        $this->_oWrapper = $oWrapper;
    }

    /**
     * Returns a generated cache key.
     *
     * @return string
     */
    public function generateCacheKey()
    {
        $aArguments = func_get_args();

        return implode('|', $aArguments);
    }

    /**
     * Adds the variable to the cache.
     *
     * @param string $sKey   The cache key
     * @param mixed  $mValue The value.
     */
    public function addToCache($sKey, $mValue)
    {
        $this->_aCache[$sKey] = $mValue;
    }

    /**
     * Returns a value from the cache by the given key.
     *
     * @param string $sKey
     *
     * @return mixed
     */
    public function getFromCache($sKey)
    {
        if (isset($this->_aCache[$sKey])) {
            return $this->_aCache[$sKey];
        }

        return null;
    }

    /**
     * Flushes the cache.
     */
    public function flushCache()
    {
        $this->_aCache = array();
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
     * @param string $sId The user id.
     *
     * @return mixed
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
            /*$iPriority = $this->_oWrapper->hasFilter('get_term', array(UserAccessManager::class, 'showTerm')); //TODO check if that really works
            $blRemoveSuccess = $this->_oWrapper->removeFilter('get_term', array(UserAccessManager::class, 'showTerm'), $iPriority);

            if ($blRemoveSuccess === true) {
                $this->_oWrapper->addFilter('get_term', array(UserAccessManager::class, 'showTerm'), $iPriority, 2);
            }*/

            $this->_aTerms[$sId] = $this->_oWrapper->getTerm($sId, $sTaxonomy);
        }

        return $this->_aTerms[$sId];
    }

}