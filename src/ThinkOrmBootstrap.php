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
 */
class ThinkOrmBootstrap implements Bootstrap
{
    public static function start($worker)
    {
        Db::setCache(new Psr16Cache(new RedisAdapter(Redis::connection(CacheConfig::get('cache_store'))->client())));
    }
}
