<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use JetBrains\PhpStorm\NoReturn;
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

class RedirectController extends Controller
{
    use LoginControllerTrait;

    public const POST_URL_CACHE_KEY = 'PostUrls';
    public const REDIRECT_TO_PARAMETER = 'redirect_to';

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        private MainConfig $mainConfig,
        private Database $database,
        private Util $util,
        private Cache $cache,
        private ObjectHandler $objectHandler,
        private AccessHandler $accessHandler,
        private FileHandler $fileHandler,
        private FileObjectFactory $fileObjectFactory
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

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

        $postId = $this->wordpress->attachmentUrlToPostId($newUrl);

        if ($postId === 0) {
            $newUrl = preg_replace('/(\\.[^.\\s]{3,4})$/', '-scaled$1', $newUrl);
            $postId = $this->wordpress->attachmentUrlToPostId($newUrl);
        }

        $postUrls[$url] = $postId;
        $this->cache->addToRuntimeCache(self::POST_URL_CACHE_KEY, $postUrls);

        return $postUrls[$url];
    }

    private function normalizeAttachmentUrl(array $uploadDirs, string $objectUrl): string
    {
        $uploadDir = str_replace(ABSPATH, '/', $uploadDirs['basedir']);
        $regex = '/.*' . str_replace('/', '\/', $uploadDir) . '\//i';
        $cleanObjectUrl = preg_replace($regex, '', $objectUrl);
        $uploadUrl = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);

        return rtrim($uploadUrl, '/') . '/' . ltrim($cleanObjectUrl, '/');
    }

    private function getAttachmentFileObject(string $objectUrl): ?FileObject
    {
        $uploadDirs = $this->wordpress->getUploadDir();
        $postId = $this->getPostIdByUrl($this->normalizeAttachmentUrl($uploadDirs, $objectUrl));

        if ($postId < 1) {
            return null;
        }

        $post = $this->objectHandler->getPost($postId);

        if (($post->post_type ?? '') !== ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            return null;
        }

        $file = $this->wordpress->getAttachedFile($post->ID);

        if ($file === false || $this->isInsideUploadDirectory($file, $uploadDirs['basedir']) === false) {
            return null;
        }

        return $this->fileObjectFactory->createFileObject(
            $post->ID,
            ObjectHandler::ATTACHMENT_OBJECT_TYPE,
            $file,
            $this->wordpress->attachmentIsImage($post->ID)
        );
    }

    private function isInsideUploadDirectory(string $file, string $uploadBaseDir): bool
    {
        $realFile = $this->php->realpath($file);
        $realUploadBaseDir = $this->php->realpath($uploadBaseDir);

        return $realFile !== false
            && $realUploadBaseDir !== false
            && str_starts_with($realFile, $realUploadBaseDir . DIRECTORY_SEPARATOR);
    }

    private function getFileSettingsByType(string $objectType, string $objectUrl): ?FileObject
    {
        if ($objectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            return $this->getAttachmentFileObject($objectUrl);
        }

        $extraParameter = $this->getRequestParameter('uamextra');

        return $this->wordpress->applyFilters(
            'uam_get_file_settings_by_type',
            null,
            $objectType,
            $objectUrl,
            $extraParameter
        );
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getFile(string $objectType, string $objectUrl): void
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
        } elseif ($redirect === 'origin') {
            $referer = $this->wordpress->getReferer();
            $url = $referer !== false ? $referer : $this->wordpress->getHomeUrl('/');
        } else {
            $url = $this->wordpress->getHomeUrl('/');
        }

        return $url;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function redirectUser(bool $checkPosts = true): void
    {
        if ($checkPosts === true) {
            $posts = $this->wordpress->getWpQuery()->get_posts();

            foreach ($posts as $post) {
                if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                    return;
                }
            }
        }

        $url = $this->getRedirectUrlAndPermalink($permalink);
        $currentUrl = $this->util->getCurrentUrl();

        if ($url !== null && $url !== $currentUrl && $permalink !== $currentUrl) {
            if ($this->mainConfig->appendRedirectToParameter() === true) {
                $url = $this->wordpress->addQueryArg([self::REDIRECT_TO_PARAMETER => $currentUrl], $url);
            }

            $this->wordpress->wpRedirect($url);
            $this->php->callExit();
        }
    }

    private function getPostIdByName(string $name): int
    {
        $postableTypes = implode('\',\'', $this->objectHandler->getPostTypes());

        $query = $this->database->prepare(
            "SELECT ID
                FROM {$this->database->getPostsTable()}
                WHERE post_name = %s
                  AND post_type IN ('$postableTypes')",
            $name
        );

        return (int) $this->database->getVariable($query);
    }

    private function extractObjectTypeAndId(mixed $pageParams, ?string &$objectType, int|string|null &$objectId): void
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
     * @throws UserGroupTypeException
     */
    public function redirect(?array $headers, mixed $pageParams): ?array
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

    public function getFileUrl(string $url, int|string|null $id): string
    {
        // Nginx always supports real urls so we need the new urls only
        // if we don't use nginx and mod_rewrite is disabled
        if ($this->mainConfig->lockFile() === true
            && $this->wordpress->isNginx() === false
            && $this->wordpress->gotModRewrite() === false
        ) {
            $post = $this->objectHandler->getPost($id);

            if ($post !== false) {
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

    public function cachePostLinks(string $url, object $post): string
    {
        $postUrls = (array) $this->cache->getFromRuntimeCache(self::POST_URL_CACHE_KEY);
        $postUrls[$url] = $post->ID;
        $this->cache->addToRuntimeCache(self::POST_URL_CACHE_KEY, $postUrls);
        return $url;
    }

    #[NoReturn]
    public function testXSendFile(): void
    {
        $this->fileHandler->deliverXSendFileTestFile();
    }
}
