<?php
/**
 * FrontendRedirectController.php
 *
 * The FrontendRedirectController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObject;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FrontendRedirectController
 *
 * @package UserAccessManager\Controller
 */
class RedirectController extends Controller
{
    use LoginControllerTrait;

    const POST_URL_CACHE_KEY = 'PostUrls';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var FileObjectFactory
     */
    private $fileObjectFactory;

    /**
     * FrontendRedirectController constructor.
     *
     * @param Php               $php
     * @param Wordpress         $wordpress
     * @param MainConfig        $config
     * @param Database          $database
     * @param Util              $util
     * @param Cache             $cache
     * @param ObjectHandler     $objectHandler
     * @param AccessHandler     $accessHandler
     * @param FileHandler       $fileHandler
     * @param FileObjectFactory $fileObjectFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        MainConfig $config,
        Database $database,
        Util $util,
        Cache $cache,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler,
        FileHandler $fileHandler,
        FileObjectFactory $fileObjectFactory
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->database = $database;
        $this->util = $util;
        $this->cache = $cache;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
        $this->fileObjectFactory = $fileObjectFactory;
    }

    /**
     * Returns the post by the given url.
     *
     * @param string $url The url of the post(attachment).
     *
     * @return int
     */
    public function getPostIdByUrl($url)
    {
        $postUrls = (array)$this->cache->getFromRuntimeCache(self::POST_URL_CACHE_KEY);

        if (isset($postUrls[$url]) === true) {
            return $postUrls[$url];
        }

        $postUrls[$url] = null;

        //Filter edit string
        $newUrlPieces = preg_split('/-e[0-9]{1,}/', $url);
        $newUrl = (count($newUrlPieces) === 2) ? $newUrlPieces[0].$newUrlPieces[1] : $newUrlPieces[0];

        //Filter size
        $newUrlPieces = preg_split('/-[0-9]{1,}x[0-9]{1,}(_[a-z])?/', $newUrl);
        $newUrl = (count($newUrlPieces) === 2) ? $newUrlPieces[0].$newUrlPieces[1] : $newUrlPieces[0];
        $newUrl = preg_replace('/\-pdf\.jpg$/', '.pdf', $newUrl);

        $query = $this->database->prepare(
            "SELECT ID
            FROM {$this->database->getPostsTable()}
            WHERE guid = '%s'
            LIMIT 1",
            $newUrl
        );

        $dbPost = $this->database->getRow($query);

        if ($dbPost !== null) {
            $postUrls[$url] = $dbPost->ID;
            $this->cache->addToRuntimeCache(self::POST_URL_CACHE_KEY, $postUrls);
        }

        return $postUrls[$url];
    }

    /**
     * Returns the file object by the given type and url.
     *
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl  The file url.
     *
     * @return null|FileObject
     */
    private function getFileSettingsByType($objectType, $objectUrl)
    {
        $fileObject = null;

        if ($objectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            $uploadDirs = $this->wordpress->getUploadDir();
            $uploadDir = str_replace(ABSPATH, '/', $uploadDirs['basedir']);
            $regex = '/.*'.str_replace('/', '\/', $uploadDir).'\//i';
            $cleanObjectUrl = preg_replace($regex, '', $objectUrl);
            $uploadUrl = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);
            $objectUrl = rtrim($uploadUrl, '/').'/'.ltrim($cleanObjectUrl, '/');

            $post = $this->objectHandler->getPost($this->getPostIdByUrl($objectUrl));

            if ($post !== false
                && $post->post_type === ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ) {
                $multiPath = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);

                $fileObject = $this->fileObjectFactory->createFileObject(
                    $post->ID,
                    $objectType,
                    $uploadDirs['basedir'].str_replace($multiPath, '', $objectUrl),
                    $this->wordpress->attachmentIsImage($post->ID)
                );
            }
        } else {
            $extraParameter = $this->getRequestParameter('uamextra');

            $fileObject = $this->wordpress->applyFilters(
                'uam_get_file_settings_by_type',
                $fileObject,
                $objectType,
                $objectUrl,
                $extraParameter
            );
        }

        return $fileObject;
    }

    /**
     * Delivers the content of the requested file.
     *
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl  The file url.
     */
    public function getFile($objectType, $objectUrl)
    {
        $fileObject = $this->getFileSettingsByType($objectType, $objectUrl);

        if ($fileObject === null) {
            return;
        }

        if ($this->accessHandler->checkObjectAccess($fileObject->getType(), $fileObject->getId()) === true) {
            $file = $fileObject->getFile();
        } elseif ($fileObject->isImage() === true) {
            $realPath = $this->config->getRealPath();
            $file = $realPath.'assets/gfx/noAccessPic.png';
        } else {
            $this->wordpress->wpDie(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);
            return;
        }

        $this->fileHandler->getFile($file, $fileObject->isImage());
    }

    /**
     * Returns the redirect url and the permalink of the post if exists.
     *
     * @param null|string $permalink
     *
     * @return null|string
     */
    private function getRedirectUrlAndPermalink(&$permalink)
    {
        $permalink = null;
        $redirect = $this->config->getRedirect();

        if ($redirect === 'custom_page') {
            $redirectCustomPage = $this->config->getRedirectCustomPage();
            $post = $this->objectHandler->getPost($redirectCustomPage);
            $url = null;

            if ($post !== false) {
                $url = $post->guid;
                $permalink = $this->wordpress->getPageLink($post);
            }
        } elseif ($redirect === 'custom_url') {
            $url = $this->config->getRedirectCustomUrl();
        } else {
            $url = $this->wordpress->getHomeUrl('/');
        }

        return $url;
    }

    /**
     * Redirects the user to his destination.
     *
     * @param bool $checkPosts
     */
    public function redirectUser($checkPosts = true)
    {
        if ($checkPosts === true) {
            $posts = (array)$this->wordpress->getWpQuery()->get_posts();

            foreach ($posts as $post) {
                if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                    return;
                }
            }
        }

        $url = $this->getRedirectUrlAndPermalink($permalink);
        $currentUrl = $this->util->getCurrentUrl();

        if ($url !== null && $url !== $currentUrl && $permalink !== $currentUrl) {
            $this->wordpress->wpRedirect($url);
            $this->php->callExit();
        }
    }

    /**
     * Extracts the object type and id.
     *
     * @param object      $pageParams
     * @param null|string $objectType
     * @param null|string $objectId
     */
    private function extractObjectTypeAndId($pageParams, &$objectType, &$objectId)
    {
        $objectType = null;
        $objectId = null;

        if (isset($pageParams->query_vars['p']) === true) {
            $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
            $objectId = $pageParams->query_vars['p'];
        } elseif (isset($pageParams->query_vars['page_id']) === true) {
            $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
            $objectId = $pageParams->query_vars['page_id'];
        } elseif (isset($pageParams->query_vars['cat_id']) === true) {
            $objectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
            $objectId = $pageParams->query_vars['cat_id'];
        } elseif (isset($pageParams->query_vars['name']) === true) {
            $postableTypes = implode('\',\'', $this->objectHandler->getPostTypes());

            $query = $this->database->prepare(
                "SELECT ID
                    FROM {$this->database->getPostsTable()}
                    WHERE post_name = %s
                      AND post_type IN ('{$postableTypes}')",
                $pageParams->query_vars['name']
            );

            $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
            $objectId = (int)$this->database->getVariable($query);
        } elseif (isset($pageParams->query_vars['pagename']) === true) {
            $object = $this->wordpress->getPageByPath($pageParams->query_vars['pagename']);

            if ($object !== null) {
                $objectType = $object->post_type;
                $objectId = $object->ID;
            }
        }
    }

    /**
     * Redirects to a page or to content.
     *
     * @param string $headers    The headers which are given from wordpress.
     * @param object $pageParams The params of the current page.
     *
     * @return string
     */
    public function redirect($headers, $pageParams)
    {
        $fileUrl = $this->getRequestParameter('uamgetfile');
        $fileType = $this->getRequestParameter('uamfiletype');

        if ($fileUrl !== null && $fileType !== null) {
            $this->getFile($fileType, $fileUrl);
        } elseif ($this->config->atAdminPanel() === false
            && $this->config->getRedirect() !== 'false'
        ) {
            $this->extractObjectTypeAndId($pageParams, $objectType, $objectId);

            if ($this->accessHandler->checkObjectAccess($objectType, $objectId) === false) {
                $this->redirectUser(false);
            }
        }

        return $headers;
    }

    /**
     * Returns the url for a locked file.
     *
     * @param string  $url The base url.
     * @param integer $id  The _iId of the file.
     *
     * @return string
     */
    public function getFileUrl($url, $id)
    {
        if ($this->config->isPermalinksActive() === false && $this->config->lockFile() === true) {
            $post = $this->objectHandler->getPost($id);

            if ($post !== null) {
                $type = explode('/', $post->post_mime_type);
                $type = (isset($type[1]) === true) ? $type[1] : $type[0];

                $lockedFileTypes = $this->config->getLockedFileTypes();
                $fileTypes = explode(',', $lockedFileTypes);

                if ($lockedFileTypes === 'all' || in_array($type, $fileTypes) === true) {
                    $url = $this->wordpress->getHomeUrl('/').'?uamfiletype=attachment&uamgetfile='.$url;
                }
            }
        }

        return $url;
    }

    /**
     * Caches the urls for the post for a later lookup.
     *
     * @param string $url  The url of the post.
     * @param object $post The post object.
     *
     * @return string
     */
    public function cachePostLinks($url, $post)
    {
        $postUrls = (array)$this->cache->getFromRuntimeCache(self::POST_URL_CACHE_KEY);
        $postUrls[$url] = $post->ID;
        $this->cache->addToRuntimeCache(self::POST_URL_CACHE_KEY, $postUrls);
        return $url;
    }
}
