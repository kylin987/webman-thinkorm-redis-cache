<?php
/**
 * @desc ThinkOrm.php 描述信息
 * @author Tinywan(ShaoBo Wan)
 * @date 2021/11/9 15:13
 */


declare(strict_types=1);

namespace support\boot;


use support\Redis;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use think\facade\Db;
use Webman\Bootstrap;

class ThinkOrm implements Bootstrap
{
    // 进程启动时调用
    public static function start($worker)
    {
        Db::setCache(new Psr16Cache(new RedisAdapter(Redis::connection(config('thinkorm.cache_store'))->client())));
    }
}