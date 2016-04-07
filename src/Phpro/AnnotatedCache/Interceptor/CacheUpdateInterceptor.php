<?php

namespace Phpro\AnnotatedCache\Interceptor;

use Cache\Adapter\Common\CacheItem;
use Phpro\AnnotatedCache\Annotation\CacheAnnotationInterface;
use Phpro\AnnotatedCache\Annotation\CacheUpdate;
use Phpro\AnnotatedCache\Cache\PoolManager;
use Phpro\AnnotatedCache\Interception\InterceptionPrefixInterface;
use Phpro\AnnotatedCache\Interception\InterceptionSuffixInterface;
use Phpro\AnnotatedCache\KeyGenerator\KeyGeneratorInterface;

/**
 * Class CacheUpdateInterceptor
 *
 * @package Phpro\AnnotatedCache\Interceptor
 */
class CacheUpdateInterceptor implements InterceptorInterface
{

    /**
     * @var PoolManager
     */
    private $poolManager;

    /**
     * @var KeyGeneratorInterface
     */
    private $keyGenerator;

    /**
     * CacheableInterceptor constructor.
     *
     * @param PoolManager           $poolManager
     * @param KeyGeneratorInterface $keyGenerator
     */
    public function __construct(PoolManager $poolManager, KeyGeneratorInterface $keyGenerator)
    {
        $this->poolManager = $poolManager;
        $this->keyGenerator = $keyGenerator;
    }

    /**
     * @param CacheAnnotationInterface $annotation
     *
     * @return bool
     */
    public function canInterceptAnnotation(CacheAnnotationInterface $annotation)
    {
        return $annotation instanceof CacheUpdate;
    }

    /**
     * @param CacheUpdate                 $annotation
     * @param InterceptionPrefixInterface $interception
     */
    public function interceptPrefix(CacheAnnotationInterface $annotation, InterceptionPrefixInterface $interception)
    {
        return null;
    }

    /**
     * @param CacheUpdate                 $annotation
     * @param InterceptionSuffixInterface $interception
     */
    public function interceptSuffix(CacheAnnotationInterface $annotation, InterceptionSuffixInterface $interception)
    {
        $key = $this->calculateKey($annotation, $interception);
        $item = new CacheItem($key);
        $item->set($interception->getReturnValue());
        $item->setTags($annotation->tags);

        if ($annotation->ttl > 0) {
            $item->expiresAfter($annotation->ttl);
        }

        foreach ($annotation->pools as $poolName) {
            $pool = $this->poolManager->getPool($poolName);
            $pool->saveDeferred($item);
        }
    }

    /**
     * @param CacheUpdate                 $annotation
     * @param InterceptionSuffixInterface $interception
     *
     * @return string
     */
    private function calculateKey(CacheAnnotationInterface $annotation, InterceptionSuffixInterface $interception)
    {
        return $this->keyGenerator->generateKey($interception->getParams(), $annotation->key);
    }


}