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

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\FileObject;
use UserAccessManager\File\FileObjectFactory;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
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
     * @var MainConfig
     */
    private $mainConfig;

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
     * RedirectController constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param Database $database
     * @param Util $util
     * @param Cache $cache
     * @param ObjectHandler $objectHandler
     * @param AccessHandler $accessHandler
     * @param FileHandler $fileHandler
     * @param FileObjectFactory $fileObjectFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Database $database,
        Util $util,
        Cache $cache,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler,
        FileHandler $fileHandler,
        FileObjectFactory $fileObjectFactory
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->mainConfig = $mainConfig;
        $this->database = $database;
        $this->util = $util;
        $this->cache = $cache;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
        $this->fileObjectFactory = $fileObjectFactory;
    }

    /**
     * @return Wordpress
     */
    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    /**
     * Returns the post by the given url.
     * @param string $url The url of the post(attachment).
     * @return int
     */
    public function getPostIdByUrl(string $url): int
    {
        $postUrls = (array)$this->cache->getFromRuntimeCache(self::POST_URL_CACHE_KEY);

        if (isset($postUrls[$url]) === true) {
            return $postUrls[$url];
        }

        //Filter size
        $newUrlPieces = preg_split('/-[0-9]+x[0-9]+(_[a-z])?/', $url);
        $newUrl = (count($newUrlPieces) === 2) ? $newUrlPieces[0] . $newUrlPieces[1] : $newUrlPieces[0];
        $newUrl = preg_replace('/-pdf\.jpg$/', '.pdf', $newUrl);

        $postUrls[$url] = $this->wordpress->attachmentUrlToPostId($newUrl);
        $this->cache->addToRuntimeCache(self::POST_URL_CACHE_KEY, $postUrls);

        return $postUrls[$url];
    }

    /**
     * Returns the file object by the given type and url.
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl The file url.
     * @return null|FileObject
     */
    private function getFileSettingsByType(string $objectType, string $objectUrl): ?FileObject
    {
        $fileObject = null;

        if ($objectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            $uploadDirs = $this->wordpress->getUploadDir();
            $uploadDir = str_replace(ABSPATH, '/', $uploadDirs['basedir']);
            $regex = '/.*' . str_replace('/', '\/', $uploadDir) . '\//i';
            $cleanObjectUrl = preg_replace($regex, '', $objectUrl);
            $uploadUrl = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);
            $objectUrl = rtrim($uploadUrl, '/') . '/' . ltrim($cleanObjectUrl, '/');

            $post = $this->objectHandler->getPost($this->getPostIdByUrl($objectUrl));
            $postType = $post->post_type ?? '';

            if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                $multiPath = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);

                $fileObject = $this->fileObjectFactory->createFileObject(
                    $post->ID,
                    $objectType,
                    $uploadDirs['basedir'] . str_replace($multiPath, '', $objectUrl),
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
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl The file url.
     * @throws UserGroupTypeException
     */
    public function getFile(string $objectType, string $objectUrl)
    {
        $fileObject = $this->getFileSettingsByType($objectType, $objectUrl);

        if ($fileObject === null) {
            return;
        }

        if ($this->accessHandler->checkObjectAccess($fileObject->getType(), $fileObject->getId()) === true) {
            $file = $fileObject->getFile();
        } elseif ($fileObject->isImage() === true) {
            if ($this->mainConfig->getNoAccessImageType() === 'custom') {
                $file = $this->mainConfig->getCustomNoAccessImage();
            } else {
                $realPath = $this->wordpressConfig->getRealPath();
                $file = $realPath . 'assets' . DIRECTORY_SEPARATOR . 'gfx' . DIRECTORY_SEPARATOR . 'noAccessPic.png';
            }
        } else {
            $this->wordpress->wpDie(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);
            return;
        }

        $this->fileHandler->getFile($file, $fileObject->isImage());
    }

    /**
     * Returns the redirect url and the permalink of the post if exists.
     * @param null|string $permalink
     * @return null|string
     */
    private function getRedirectUrlAndPermalink(?string &$permalink): ?string
    {
        $permalink = null;
        $redirect = $this->mainConfig->getRedirect();

        if ($redirect === 'custom_page') {
            $redirectCustomPage = $this->mainConfig->getRedirectCustomPage();
            $post = $this->objectHandler->getPost($redirectCustomPage);
            $url = null;

            if ($post !== false) {
                $url = $post->guid;
                $permalink = $this->wordpress->getPageLink($post);
            }
        } elseif ($redirect === 'custom_url') {
            $url = $this->mainConfig->getRedirectCustomUrl();
        } elseif ($redirect === 'login') {
            $url = $this->getLoginUrl();
        } else {
            $url = $this->wordpress->getHomeUrl('/');
        }

        return $url;
    }

    /**
     * Redirects the user to his destination.
     * @param bool $checkPosts
     * @throws UserGroupTypeException
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
     * Returns the post id by the post name.
     * @param string $name
     * @return int
     */
    private function getPostIdByName(string $name): int
    {
        $postableTypes = implode('\',\'', $this->objectHandler->getPostTypes());

        $query = $this->database->prepare(
            "SELECT ID
                FROM {$this->database->getPostsTable()}
                WHERE post_name = %s
                  AND post_type IN ('{$postableTypes}')",
            $name
        );

        return (int) $this->database->getVariable($query);
    }

    /**
     * Extracts the object type and id.
     * @param mixed $pageParams
     * @param null|string $objectType
     * @param null|int|string $objectId
     */
    private function extractObjectTypeAndId($pageParams, ?string &$objectType, ?string &$objectId)
    {
        $objectType = null;
        $objectId = null;

        $simpleTypes = [
            'p' => ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            'page_id' => ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            'cat_id' => ObjectHandler::GENERAL_TERM_OBJECT_TYPE
        ];

        foreach ($simpleTypes as $queryVar => $newObjectType) {
            if (isset($pageParams->query_vars[$queryVar]) === true) {
                $objectType = $newObjectType;
                $objectId = $pageParams->query_vars[$queryVar];
            }
        }

        if (isset($pageParams->query_vars['name']) === true) {
            $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
            $objectId = $this->getPostIdByName($pageParams->query_vars['name']);
        } elseif (isset($pageParams->query_vars['pagename']) === true) {
            $object = $this->wordpress->getPageByPath($pageParams->query_vars['pagename']);

            if ($object !== null) {
                $objectType = $object->post_type ?? null;
                $objectId = $object->ID ?? null;
            }
        }
    }

    /**
     * Redirects to a page or to content.
     * @param array|null $headers The headers which are given from wordpress.
     * @param mixed $pageParams The params of the current page.
     * @return array|null
     * @throws UserGroupTypeException
     */
    public function redirect(?array $headers, $pageParams): ?array
    {
        $fileUrl = $this->getRequestParameter('uamgetfile');
        $fileType = $this->getRequestParameter('uamfiletype');

        if ($fileUrl !== null && $fileType !== null) {
            $this->getFile($fileType, $fileUrl);
        } elseif ($this->wordpressConfig->atAdminPanel() === false
            && $this->mainConfig->getRedirect() !== 'false'
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
     * @param string $url The base url.
     * @param int|string $id The id of the file.
     * @return string
     */
    public function getFileUrl(string $url, $id): string
    {
        // Nginx always supports real urls so we need the new urls only
        // if we don't use nginx and mod_rewrite is disabled
        if ($this->mainConfig->lockFile() === true
            && $this->wordpress->isNginx() === false
            && $this->wordpress->gotModRewrite() === false
        ) {
            $post = $this->objectHandler->getPost($id);

            if ($post !== null) {
                $type = explode('/', $post->post_mime_type);
                $type = $type[1] ?? $type[0];

                $lockedFileTypes = $this->mainConfig->getLockedFiles();
                $fileTypes = explode(',', $lockedFileTypes);

                if ($lockedFileTypes === 'all' || in_array($type, $fileTypes) === true) {
                    $url = $this->wordpress->getHomeUrl('/') . '?uamfiletype=attachment&uamgetfile=' . $url;
                }
            }
        }

        return $url;
    }

    /**
     * Caches the urls for the post for a later lookup.
     * @param string $url The url of the post.
     * @param object $post The post object.
     * @return string
     */
    public function cachePostLinks(string $url, object $post): string
    {
        $postUrls = (array) $this->cache->getFromRuntimeCache(self::POST_URL_CACHE_KEY);
        $postUrls[$url] = $post->ID;
        $this->cache->addToRuntimeCache(self::POST_URL_CACHE_KEY, $postUrls);
        return $url;
    }

    /**
     * Tries to load the file via x send file
     */
    public function testXSendFile()
    {
        $this->fileHandler->deliverXSendFileTestFile();
    }
}
