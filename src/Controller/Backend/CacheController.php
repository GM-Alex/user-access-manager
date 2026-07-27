<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Object\ObjectMapHandler;

class CacheController
{
    public function __construct(
        private Cache $cache
    ) {
    }

    public function invalidateTermCache(): void
    {
        $this->cache->invalidate(ObjectMapHandler::POST_TERM_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::TERM_POST_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::TERM_TREE_MAP_CACHE_KEY);
    }

    public function invalidatePostCache(): void
    {
        $this->cache->invalidate(ObjectMapHandler::TERM_POST_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::POST_TERM_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::POST_TREE_MAP_CACHE_KEY);
    }

    /**
     * Flushes all UAM caches when a user group is created, updated, or deleted.
     *
     * Group changes affect access decisions and user group memberships across
     * the entire site. Rather than trying to selectively invalidate individual
     * cache entries — which would require tracking every user and object
     * combination — we flush the full cache. This is safe because the cache
     * is rebuilt on the next request.
     */
    public function invalidateUserGroupCaches(): void
    {
        $this->cache->flushCache();
    }
}
