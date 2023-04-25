<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\PageCache\UrlRewrite;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\GraphQl\PageCache\GraphQLPageCacheAbstract;
use Magento\GraphQlCache\Model\CacheId\CacheIdCalculator;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test cache works properly for url resolver.
 */
class UrlResolverCacheTest extends GraphQLPageCacheAbstract
{
    /**
     * Tests cache invalidation for product urlResolver
     *
     * @magentoConfigFixture default/system/full_page_cache/caching_application 2
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     */
    public function testUrlResolverCachingForProducts()
    {
        $urlKey = 'p002.html';
        $urlResolverQuery = $this->getUrlResolverQuery($urlKey);

        // Obtain the X-Magento-Cache-Id from the response which will be used as the cache key
        $response = $this->graphQlQueryWithResponseHeaders($urlResolverQuery);
        $this->assertArrayHasKey(CacheIdCalculator::CACHE_ID_HEADER, $response['headers']);
        $cacheId = $response['headers'][CacheIdCalculator::CACHE_ID_HEADER];
        // Verify we obtain a cache MISS the first time we search the cache using this X-Magento-Cache-Id
        $this->assertCacheMissAndReturnResponse($urlResolverQuery, [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]);
        // Verify we obtain a cache HIT the second time around for this X-Magento-Cache-Id
        $cachedResponse = $this->assertCacheHitAndReturnResponse(
            $urlResolverQuery,
            [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]
        );

        //cached data should be correct
        $this->assertNotEmpty($cachedResponse['body']);
        $this->assertArrayNotHasKey('errors', $cachedResponse['body']);
        $this->assertEquals('PRODUCT', $cachedResponse['body']['urlResolver']['type']);
    }

    /**
     * Tests cache invalidation for category urlResolver
     *
     * @magentoConfigFixture default/system/full_page_cache/caching_application 2
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     */
    public function testUrlResolverCachingForCategory()
    {
        $categoryUrlKey = 'cat-1.html';
        $query = $this->getUrlResolverQuery($categoryUrlKey);

        $response = $this->graphQlQueryWithResponseHeaders($query);
        $this->assertArrayHasKey(CacheIdCalculator::CACHE_ID_HEADER, $response['headers']);
        $cacheId = $response['headers'][CacheIdCalculator::CACHE_ID_HEADER];
        // Verify we obtain a cache MISS the first time we search the cache using this X-Magento-Cache-Id
        $this->assertCacheMissAndReturnResponse($query, [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]);
        // Verify we obtain a cache HIT the second time around for this X-Magento-Cache-Id
        $cachedResponse = $this->assertCacheHitAndReturnResponse(
            $query,
            [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]
        );

        //verify cached data is correct
        $this->assertNotEmpty($cachedResponse['body']);
        $this->assertArrayNotHasKey('errors', $cachedResponse['body']);
        $this->assertEquals('CATEGORY', $cachedResponse['body']['urlResolver']['type']);
    }

    /**
     * Test cache invalidation for cms page url resolver
     *
     * @magentoConfigFixture default/system/full_page_cache/caching_application 2
     * @magentoApiDataFixture Magento/Cms/_files/pages.php
     */
    public function testUrlResolverCachingForCMSPage()
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = Bootstrap::getObjectManager()->get(\Magento\Cms\Model\Page::class);
        $page->load('page100');
        $requestPath = $page->getIdentifier();

        $query = $this->getUrlResolverQuery($requestPath);
        $response = $this->graphQlQueryWithResponseHeaders($query);
        $this->assertArrayHasKey(CacheIdCalculator::CACHE_ID_HEADER, $response['headers']);
        $cacheId = $response['headers'][CacheIdCalculator::CACHE_ID_HEADER];
        // Verify we obtain a cache MISS the first time we search the cache using this X-Magento-Cache-Id
        $this->assertCacheMissAndReturnResponse($query, [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]);
        // Verify we obtain a cache HIT the second time around for this X-Magento-Cache-Id
        $cachedResponse = $this->assertCacheHitAndReturnResponse(
            $query,
            [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]
        );

        //verify cached data is correct
        $this->assertNotEmpty($cachedResponse['body']);
        $this->assertArrayNotHasKey('errors', $cachedResponse['body']);
        $this->assertEquals('CMS_PAGE', $cachedResponse['body']['urlResolver']['type']);
    }

    /**
     * Tests that cache is invalidated when url key is updated and
     * access the original request path
     *
     * @magentoConfigFixture default/system/full_page_cache/caching_application 2
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     */
    public function testCacheIsInvalidatedForUrlResolver()
    {
        $productSku = 'p002';
        $urlKey = 'p002.html';
        $urlResolverQuery = $this->getUrlResolverQuery($urlKey);

        // Obtain the X-Magento-Cache-Id from the response which will be used as the cache key
        $response = $this->graphQlQueryWithResponseHeaders($urlResolverQuery);
        $this->assertArrayHasKey(CacheIdCalculator::CACHE_ID_HEADER, $response['headers']);
        $cacheId = $response['headers'][CacheIdCalculator::CACHE_ID_HEADER];
        // Verify we obtain a cache MISS the first time we search the cache using this X-Magento-Cache-Id
        $this->assertCacheMissAndReturnResponse($urlResolverQuery, [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]);
        // Verify we obtain a cache HIT the second time around for this X-Magento-Cache-Id
        $this->assertCacheHitAndReturnResponse($urlResolverQuery, [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        /** @var Product $product */
        $product = $productRepository->get($productSku, false, null, true);
        $product->setUrlKey('p002-new.html')->save();

        // Verify we obtain a cache MISS first time we search the cache using this X-Magento-Cache-Id
        $this->assertCacheMissAndReturnResponse($urlResolverQuery, [CacheIdCalculator::CACHE_ID_HEADER => $cacheId]);
    }

    /**
     * Get url resolver query
     *
     * @param $urlKey
     * @return string
     */
    private function getUrlResolverQuery(string $urlKey): string
    {
        $query = <<<QUERY
{
  urlResolver(url:"{$urlKey}")
  {
   id
   relative_url
   canonical_url
   type
  }
}
QUERY;
        return $query;
    }
}
