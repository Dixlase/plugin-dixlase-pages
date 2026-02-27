<?php

namespace Plugins\DixlasePages\Tests\Unit;

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use Plugins\DixlasePages\App\Providers\DixlasePagesServiceProvider;
use Tests\TestCase;

/**
 * DixlasePages RouteSlugProvider ユニットテスト
 */
class DixlasePagesRouteSlugProviderTest extends TestCase
{
    /**
     * ServiceProvider が RouteSlugProvider を実装していることを検証
     */
    public function test_service_provider_implements_route_slug_provider(): void
    {
        $this->assertTrue(
            is_subclass_of(DixlasePagesServiceProvider::class, RouteSlugProvider::class)
        );
    }

    /**
     * getRouteSlugs が RegisteredSlug 配列を返すことを検証
     */
    public function test_get_route_slugs_returns_registered_slug_array(): void
    {
        $provider = $this->app->make(DixlasePagesServiceProvider::class, ['app' => $this->app]);
        $slugs = $provider->getRouteSlugs();

        $this->assertIsArray($slugs);
        $this->assertNotEmpty($slugs);
        $this->assertContainsOnlyInstancesOf(RegisteredSlug::class, $slugs);
    }

    /**
     * getRouteSlugs のスラッグが正しい owner を持つことを検証
     */
    public function test_get_route_slugs_has_correct_owner(): void
    {
        $provider = $this->app->make(DixlasePagesServiceProvider::class, ['app' => $this->app]);
        $slugs = $provider->getRouteSlugs();

        $slug = $slugs[0];
        $this->assertSame('dixlase-pages:directory', $slug->owner);
    }

    /**
     * getRouteSlugs のスラッグが正しい翻訳ラベルを持つことを検証
     */
    public function test_get_route_slugs_has_correct_label(): void
    {
        $provider = $this->app->make(DixlasePagesServiceProvider::class, ['app' => $this->app]);
        $slugs = $provider->getRouteSlugs();

        $slug = $slugs[0];
        $this->assertSame('dixlase-pages::route-slug.owners.directory', $slug->label);
    }

    /**
     * getRouteSlugs がデフォルト値 'pages' を返すことを検証
     */
    public function test_get_route_slugs_returns_default_slug(): void
    {
        $provider = $this->app->make(DixlasePagesServiceProvider::class, ['app' => $this->app]);
        $slugs = $provider->getRouteSlugs();

        $slug = $slugs[0];
        $this->assertSame('pages', $slug->slug);
    }

    /**
     * getRouteSlugs のスラッグが isReserved=false であることを検証
     */
    public function test_get_route_slugs_is_not_reserved(): void
    {
        $provider = $this->app->make(DixlasePagesServiceProvider::class, ['app' => $this->app]);
        $slugs = $provider->getRouteSlugs();

        $slug = $slugs[0];
        $this->assertFalse($slug->isReserved);
    }
}
