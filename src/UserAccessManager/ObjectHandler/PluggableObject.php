<?php
/**
 * PluggableObject.php
 *
 * The PluggableObject unit test class file.
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

/**
 * Class PluggableObject
 *
 * @package UserAccessManager\ObjectHandler
 */
abstract class PluggableObject
{
    /**
     * @var string
     */
    protected $_sName;

    /**
     * @var string
     */
    protected $_sReference;

    public function __construct($sName, $sReference)
    {
        $this->_sName = $sName;
        $this->_sReference = $sReference;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_sName;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->_sReference;
    }

    abstract public function getFull();
    abstract public function getFullObjects();
}