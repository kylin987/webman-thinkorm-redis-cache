<?php

declare(strict_types=1);

namespace Kylin987\ThinkOrm\RedisCache\Tests\Integration;

use Kylin987\ThinkOrm\RedisCache\Tests\Support\TestCase;
use Kylin987\ThinkOrm\RedisCache\ThinkOrmBootstrap;
use think\Container;

/**
 * 验证启动项不会重复注入缓存：当 config/bootstrap.php 已注册旧版
 * support\boot\ThinkOrm 时，插件 Bootstrap 应跳过 Db::setCache()。
 */
final class ThinkOrmBootstrapTest extends TestCase
{
    public function testLegacyBootstrapRegisteredPreventsDoubleInjection(): void
    {
        $this->loadConfig([
            'bootstrap.php' => [ThinkOrmBootstrap::LEGACY_BOOTSTRAP],
            'thinkorm.php'  => ['cache_store' => 'ormCache'],
            'plugin/kylin987/webman-thinkorm-redis-cache/app.php'       => ['enable' => true],
            'plugin/kylin987/webman-thinkorm-redis-cache/bootstrap.php' => [ThinkOrmBootstrap::class],
        ]);

        $spy = $this->makeSpy();
        Container::getInstance()->instance('think\DbManager', $spy);

        ThinkOrmBootstrap::start(null);

        self::assertSame(0, $spy->setCacheCalls);
    }

    public function testInjectsCacheWhenNoLegacyBootstrap(): void
    {
        if (!class_exists(\Redis::class)) {
            self::markTestSkipped('phpredis extension not loaded');
        }

        $this->loadConfig([
            'bootstrap.php' => [],
            'thinkorm.php'  => ['cache_store' => 'ormCache'],
            'plugin/kylin987/webman-thinkorm-redis-cache/app.php'       => ['enable' => true],
            'plugin/kylin987/webman-thinkorm-redis-cache/bootstrap.php' => [ThinkOrmBootstrap::class],
        ]);

        $spy = $this->makeSpy();
        Container::getInstance()->instance('think\DbManager', $spy);

        ThinkOrmBootstrap::start(null);

        self::assertSame(1, $spy->setCacheCalls);
    }

    /**
     * 一个记录 setCache() 调用次数的 DbManager 替身。
     */
    private function makeSpy(): object
    {
        return new class extends \think\DbManager {
            public int $setCacheCalls = 0;

            public function setCache(\Psr\SimpleCache\CacheInterface $cache): void
            {
                $this->setCacheCalls++;
            }
        };
    }
}
