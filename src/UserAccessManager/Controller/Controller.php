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
 * @version   SVN: $id$
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
     * @var string
     */
    protected $template = null;

    /**
     * @var Php
     */
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $updateMessage = null;

    /**
     * Controller constructor.
     *
     * @param Php       $php
     * @param Wordpress $wordpress
     * @param Config    $config
     */
    public function __construct(Php $php, Wordpress $wordpress, Config $config)
    {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;
    }

    /**
     * Sanitize the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function sanitizeValue($value)
    {
        if (is_object($value) === true) {
            return $value;
        } elseif (is_array($value) === true) {
            $newValue = [];

            foreach ($value as $key => $arrayValue) {
                $sanitizedKey = $this->sanitizeValue($key);
                $newValue[$sanitizedKey] = $this->sanitizeValue($arrayValue);
            }

            $value = $newValue;
        } elseif (is_string($value) === true) {
            $value = htmlspecialchars($value);
        }

        return $value;
    }

    /**
     * Returns the request parameter.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getRequestParameter($name, $default = null)
    {
        $return = (isset($_POST[$name]) === true) ? $this->sanitizeValue($_POST[$name]) : null;

        if ($return === null) {
            $return = (isset($_GET[$name]) === true) ? $this->sanitizeValue($_GET[$name]) : $default;
        }

        return $return;
    }

    /**
     * Returns the current request url.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return htmlentities($_SERVER['REQUEST_URI']);
    }

    /**
     * Returns the nonce field.
     *
     * @param string $name
     *
     * @return string
     */
    public function createNonceField($name)
    {
        return $this->wordpress->getNonceField($name, $name.'Nonce');
    }

    /**
     * Returns the nonce.
     *
     * @param string $name
     *
     * @return string
     */
    public function getNonce($name)
    {
        return $this->wordpress->createNonce($name);
    }

    /**
     * Verifies the nonce and terminates the application if the nonce is wrong.
     *
     * @param string $name
     */
    protected function verifyNonce($name)
    {
        $nonce = $this->getRequestParameter($name.'Nonce');

        if ($this->wordpress->verifyNonce($nonce, $name) === false) {
            $this->wordpress->wpDie(TXT_UAM_NONCE_FAILURE_MESSAGE, TXT_UAM_NONCE_FAILURE_TITLE, ['response' => 401]);
        }
    }

    /**
     * Sets the update message.
     *
     * @param $message
     */
    protected function setUpdateMessage($message)
    {
        $this->updateMessage = $message;
    }

    /**
     * Returns the update message.
     *
     * @return string
     */
    public function getUpdateMessage()
    {
        return $this->updateMessage;
    }

    /**
     * Returns true if a update message is set.
     *
     * @return bool
     */
    public function hasUpdateMessage()
    {
        return $this->updateMessage !== null;
    }

    /**
     * Process the action.
     */
    protected function processAction()
    {
        $postAction = $this->getRequestParameter(self::ACTION_PARAMETER);
        $postActionSplit = explode('_', $postAction);
        $postAction = array_shift($postActionSplit);
        $postAction .= implode('', array_map('ucfirst', $postActionSplit));
        $actionMethod = $postAction.self::ACTION_SUFFIX;

        if (method_exists($this, $actionMethod) === true) {
            $this->{$actionMethod}();
        }
    }

    /**
     * Returns the content of the excluded php file.
     *
     * @param string $fileName The view file name
     *
     * @return string
     */
    protected function getIncludeContents($fileName)
    {
        $contents = '';
        $realPath = $this->config->getRealPath();
        $path = [$realPath, 'src', 'UserAccessManager', 'View'];
        $path = implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR;
        $fileWithPath = $path.$fileName;

        if (is_file($fileWithPath) === true) {
            ob_start();
            $this->php->includeFile($this, $fileWithPath);
            $contents = ob_get_contents();
            ob_end_clean();
        }

        return $contents;
    }

    /**
     * Renders the given template
     */
    public function render()
    {
        $this->processAction();

        if ($this->template !== null) {
            echo $this->getIncludeContents($this->template);
        }
    }
}
