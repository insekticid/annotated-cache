<?php

namespace PhproTest\AnnotatedCache\Functional;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Phpro\AnnotatedCache\Cache\PoolManagerInterface;
use Phpro\AnnotatedCache\Factory;
use Phpro\AnnotatedCache\KeyGenerator\KeyGeneratorInterface;
use PhproTest\AnnotatedCache\Objects\Book;
use PhproTest\AnnotatedCache\Objects\BookService;
use PhproTest\AnnotatedCache\Objects\TestAnnotation;
use PhproTest\AnnotatedCache\Objects\TestInterception;

class CacheAnnotationsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PoolManagerInterface
     */
    private $poolManager;

    /**
     * @var KeyGeneratorInterface $keyGenerator
     */
    private $keyGenerator;

    /**
     * @var BookService
     */
    private $service;

    protected function setUp()
    {
        $poolManager = Factory::createPoolManager();
        $poolManager->addPool('books', new ArrayCachePool());
        $cacheHandler = Factory::createCacheHandler($poolManager);
        $proxyGenerator = Factory::createProxyGenerator($cacheHandler);

        $this->poolManager = $poolManager;
        $this->keyGenerator = Factory::createKeyGenerator();
        $this->service = $proxyGenerator->generate(new BookService());
    }

    /**
     * @param $cacheKey
     *
     * @return \Psr\Cache\CacheItemInterface
     */
    private function getCacheItem($cacheKey)
    {
        return $this->poolManager->getPool('books')->getItem($cacheKey);
    }

    /**
     * @param $instance
     * @param $method
     * @param $key
     * @param $parameters
     *
     * @return string
     */
    private function calculateKey($instance, $method, $key, $parameters)
    {
        $interception = new TestInterception($instance, $method, $parameters);
        $annotation = new TestAnnotation(['key' => $key]);

        return $this->keyGenerator->generateKey($interception, $annotation);
    }

    /**
     * @test
     */
    function it_caches_values_with_cacheable()
    {
        $isbn = 'foobar123';
        $cacheKey = $this->calculateKey($this->service, 'getBookByIsbn', 'isbn', ['isbn' => $isbn]);

        $book = $this->service->getBookByIsbn($isbn);
        $cachedBook = $this->getCacheItem($cacheKey);

        $this->assertEquals($book, $cachedBook->get());
    }

    /**
     * @test
     */
    function it_uses_the_cached_value_on_next_call_with_cacheable()
    {
        $isbn = 'foobaz123';
        $cacheKey = $this->calculateKey($this->service, 'getBookByIsbn', 'isbn', ['isbn' => $isbn]);

        $book = $this->service->getBookByIsbn($isbn);
        $cachedBook = $this->getCacheItem($cacheKey);
        $supposedCachedBook = $this->service->getBookByIsbn($isbn);

        $this->assertSame($cachedBook->get(), $supposedCachedBook);
    }

    /**
     * @test
     */
    function it_updates_cache_keys_with_cacheupdate()
    {
        $book = new Book('foo123');
        $cacheKey = $this->calculateKey($this->service, 'saveBook', 'book.isbn', ['book' => $book]);

        $this->service->saveBook($book);
        $cachedBook = $this->getCacheItem($cacheKey);

        $this->assertEquals($book, $cachedBook->get());
    }

    /**
     * @test
     */
    public function it_removes_cahce_keys_with_cacheevict()
    {
        $book = new Book('foobarbaz789');
        $cacheKey = $this->calculateKey($this->service, 'saveBook', 'book.isbn', ['book' => $book]);

        $this->service->saveBook($book);
        $cachedBook = $this->getCacheItem($cacheKey);
        $this->assertEquals($book, $cachedBook->get());

        $this->service->removeBook($book);
        $cachedBook = $this->getCacheItem($cacheKey);
        $this->assertNull($cachedBook->get());
        $this->assertFalse($cachedBook->isHit());
    }
}
