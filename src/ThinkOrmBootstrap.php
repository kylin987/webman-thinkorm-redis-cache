<?php

declare(strict_types=1);

namespace Kylin987\ThinkOrm\RedisCache;

use support\Redis;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use think\facade\Db;
use Webman\Bootstrap;

/**
 * 进程启动时给 think-orm 注入 redis 缓存。
 *
 * 通过插件 bootstrap.php 自动注册，无需手动复制到 support/boot
 * 或修改 config/bootstrap.php。
 *
 * 兼容老项目：老项目可能已在 config/bootstrap.php 注册了旧启动类
 * \support\boot\ThinkOrm，此时 webman 会先执行旧启动项，再执行本插件启动项。
 * 为避免对 Db 重复 setCache()，这里先检查 config('bootstrap')，若旧启动项
 * 仍存在则跳过注入。迁移步骤见 README。
 */
class ThinkOrmBootstrap implements Bootstrap
{
    /**
     * 旧版启动类 FQCN（老项目会把它注册到 config/bootstrap.php）。
     */
    public const LEGACY_BOOTSTRAP = 'support\boot\ThinkOrm';

    public static function start($worker)
    {
        // 老项目已注册旧启动项时，不再二次注入，避免覆盖已有缓存对象
        if (self::legacyBootstrapRegistered()) {
            return;
        }

        Db::setCache(new Psr16Cache(new RedisAdapter(Redis::connection(CacheConfig::get('cache_store'))->client())));
    }

    /**
     * 判断 config/bootstrap.php 是否已注册旧版 \support\boot\ThinkOrm。
     *
     * @return bool
     */
    public static function legacyBootstrapRegistered(): bool
    {
        $bootstraps = config('bootstrap', []);
        return is_array($bootstraps) && in_array(self::LEGACY_BOOTSTRAP, $bootstraps, true);
    }
}
