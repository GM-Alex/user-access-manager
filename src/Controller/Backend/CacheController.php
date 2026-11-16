<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Object\ObjectMapHandler;

class CacheController
{
    public function __construct(
        private Cache $cache,
        private ObjectMapHandler $objectMapHandler
    ) {
    }

    public function invalidateTermCache(): void
    {
        $this->cache->invalidate(ObjectMapHandler::POST_TERM_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::TERM_POST_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::TERM_TREE_MAP_CACHE_KEY);
        $this->objectMapHandler->flushMaps();
    }

    public function invalidatePostCache(): void
    {
        $this->cache->invalidate(ObjectMapHandler::TERM_POST_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::POST_TERM_MAP_CACHE_KEY);
        $this->cache->invalidate(ObjectMapHandler::POST_TREE_MAP_CACHE_KEY);
        $this->objectMapHandler->flushMaps();
    }
}
