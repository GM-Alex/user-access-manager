<?php
/**
 * BaseControllerTrait.php
 *
 * The BaseControllerTrait trait file.
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

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;

/**
 * Trait BaseControllerTrait
 *
 * @package UserAccessManager\Controller
 */
trait BaseControllerTrait
{
    /**
     * @return Php
     */
    abstract protected function getPhp();

    /**
     * @return WordpressConfig
     */
    abstract protected function getWordpressConfig();

    /**
     * @var string
     */
    protected $template = null;

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
            $value = preg_replace('/[\\\\]+(["|\']{1})/', '$1', $value);
            $value = stripslashes($value);
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
     * Returns the content of the excluded php file.
     *
     * @param string $fileName The view file name
     *
     * @return string
     */
    protected function getIncludeContents($fileName)
    {
        $contents = '';
        $realPath = rtrim($this->getWordpressConfig()->getRealPath(), DIRECTORY_SEPARATOR);
        $path = [$realPath, 'src', 'View'];
        $path = implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR;
        $fileWithPath = $path.$fileName;

        if (is_file($fileWithPath) === true) {
            try {
                ob_start();
                $this->getPhp()->includeFile($this, $fileWithPath);
                $contents = ob_get_contents();
                ob_end_clean();
            } catch (\Exception $exception) {
                $contents = "Error on including content '{$fileWithPath}': {$exception->getMessage()}";
                ob_end_clean();
            }
        }

        return $contents;
    }

    /**
     * Renders the given template
     */
    public function render()
    {
        if ($this->template !== null) {
            echo $this->getIncludeContents($this->template);
        }
    }
}
