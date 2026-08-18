<?php

declare(strict_types=1);

namespace Kylin987\ThinkOrm\RedisCache\Tests\Integration;

use Kylin987\ThinkOrm\RedisCache\CacheConfig;
use Kylin987\ThinkOrm\RedisCache\Tests\Support\TestCase;

/**
 * 验证配置读取的兼容优先级：thinkorm.* 优先，缺失时回退到插件配置。
 */
final class CacheConfigTest extends TestCase
{
    public function testLegacyThinkormConfigTakesEffect(): void
    {
        // 仅存在旧版 thinkorm.* 配置（老项目升级后未迁移）
        $this->loadConfig([
            'thinkorm.php' => [
                'cache_store'   => 'legacyStore',
                'cache_exptime' => 111,
                'cache_always'  => false,
            ],
        ]);

        self::assertSame('legacyStore', CacheConfig::get('cache_store'));
        self::assertSame(111, CacheConfig::get('cache_exptime'));
        // false 也能被正确返回，说明用的是“!== null”判断而非 truthy 判断
        self::assertSame(false, CacheConfig::get('cache_always', true));
    }

    public function testLegacyConfigTakesPriorityOverPluginConfig(): void
    {
        // 新旧配置同时存在时，旧 thinkorm.* 优先
        $this->loadConfig([
            'thinkorm.php' => [
                'cache_store'   => 'legacyStore',
                'cache_exptime' => 111,
                'cache_always'  => false,
            ],
            'plugin/kylin987/webman-thinkorm-redis-cache/app.php' => ['enable' => true],
            'plugin/kylin987/webman-thinkorm-redis-cache/config.php' => [
                'cache_store'   => 'pluginStore',
                'cache_exptime' => 222,
                'cache_always'  => true,
            ],
        ]);

        self::assertSame('legacyStore', CacheConfig::get('cache_store'));
        self::assertSame(111, CacheConfig::get('cache_exptime'));
        self::assertSame(false, CacheConfig::get('cache_always', true));
    }

    public function testPluginConfigTakesEffectForNewProject(): void
    {
        // 新项目：仅存在插件配置，无 thinkorm.* 缓存键
        $this->loadConfig([
            'plugin/kylin987/webman-thinkorm-redis-cache/app.php' => ['enable' => true],
            'plugin/kylin987/webman-thinkorm-redis-cache/config.php' => [
                'cache_store'   => 'ormCache',
                'cache_exptime' => 172800,
                'cache_always'  => true,
            ],
        ]);

        self::assertSame('ormCache', CacheConfig::get('cache_store'));
        self::assertSame(172800, CacheConfig::get('cache_exptime'));
        self::assertSame(true, CacheConfig::get('cache_always', false));
    }

    public function testFallbackToDefaultWhenNeitherConfigured(): void
    {
        // 完全未配置时使用传入的默认值
        $this->loadConfig([]);

        self::assertNull(CacheConfig::get('cache_store'));
        self::assertSame('fallback', CacheConfig::get('cache_store', 'fallback'));
    }
}
