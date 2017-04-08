<?php
/**
 * Controller.php
 *
 * The Controller class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\Config\Config;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Controller
 *
 * @package UserAccessManager\Controller
 */
abstract class Controller
{
    const ACTION_PARAMETER = 'uam_action';
    const ACTION_SUFFIX = 'Action';

    /**
     * @var Php
     */
    protected $Php;

    /**
     * @var Wordpress
     */
    protected $Wordpress;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var string
     */
    protected $sTemplate = null;

    /**
     * @var string
     */
    protected $sUpdateMessage = null;

    /**
     * Controller constructor.
     *
     * @param Php       $Php
     * @param Wordpress $Wordpress
     * @param Config    $Config
     */
    public function __construct(Php $Php, Wordpress $Wordpress, Config $Config)
    {
        $this->Php = $Php;
        $this->Wordpress = $Wordpress;
        $this->Config = $Config;
    }

    /**
     * Returns the request parameter.
     *
     * @param string $sName
     * @param mixed  $mDefault
     *
     * @return mixed
     */
    public function getRequestParameter($sName, $mDefault = null)
    {
        $mReturn = isset($_POST[$sName]) ? $_POST[$sName] : null;

        if ($mReturn === null) {
            $mReturn = isset($_GET[$sName]) ? $_GET[$sName] : $mDefault;
        }

        return $mReturn;
    }

    /**
     * Returns the current request url.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * Returns the nonce field.
     *
     * @param string $sName
     *
     * @return string
     */
    public function createNonceField($sName)
    {
        return $this->Wordpress->getNonceField($sName, $sName.'Nonce');
    }

    /**
     * Returns the nonce.
     *
     * @param string $sName
     *
     * @return string
     */
    public function getNonce($sName)
    {
        return $this->Wordpress->createNonce($sName);
    }

    /**
     * Verifies the nonce and terminates the application if the nonce is wrong.
     *
     * @param $sName
     */
    protected function verifyNonce($sName)
    {
        $sNonce = $this->getRequestParameter($sName.'Nonce');

        if ($this->Wordpress->verifyNonce($sNonce, $sName) === false) {
            $this->Wordpress->wpDie(TXT_UAM_NONCE_FAILURE);
        }
    }

    /**
     * Sets the update message.
     *
     * @param $sMessage
     */
    protected function setUpdateMessage($sMessage)
    {
        $this->sUpdateMessage = $sMessage;
    }

    /**
     * Returns the update message.
     *
     * @return string
     */
    public function getUpdateMessage()
    {
        return $this->sUpdateMessage;
    }

    /**
     * Returns true if a update message is set.
     *
     * @return bool
     */
    public function hasUpdateMessage()
    {
        return $this->sUpdateMessage !== null;
    }

    /**
     * Process the action.
     */
    protected function processAction()
    {
        $sPostAction = $this->getRequestParameter(self::ACTION_PARAMETER);
        $aPostAction = explode('_', $sPostAction);
        $sPostAction = array_shift($aPostAction);
        $sPostAction .= implode('', array_map('ucfirst', $aPostAction));
        $sActionMethod = $sPostAction.self::ACTION_SUFFIX;

        if (method_exists($this, $sActionMethod) === true) {
            $this->{$sActionMethod}();
        }
    }

    /**
     * Returns the content of the excluded php file.
     *
     * @param string $sFileName The view file name
     *
     * @return string
     */
    protected function getIncludeContents($sFileName)
    {
        $sContents = '';
        $sRealPath = $this->Config->getRealPath();
        $aPath = [$sRealPath, 'src', 'UserAccessManager', 'View'];
        $sPath = implode(DIRECTORY_SEPARATOR, $aPath).DIRECTORY_SEPARATOR;
        $sFileWithPath = $sPath.$sFileName;

        if (is_file($sFileWithPath) === true) {
            ob_start();
            $this->Php->includeFile($this, $sFileWithPath);
            $sContents = ob_get_contents();
            ob_end_clean();
        }

        return $sContents;
    }

    /**
     * Renders the given template
     */
    public function render()
    {
        $this->processAction();

        if ($this->sTemplate !== null) {
            echo $this->getIncludeContents($this->sTemplate);
        }
    }
}
