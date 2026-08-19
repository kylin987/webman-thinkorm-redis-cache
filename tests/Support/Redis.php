<?php

declare(strict_types=1);

namespace support;

/**
 * 测试替身：本包在运行时依赖宿主项目中的 webman/redis 提供的 support\Redis，
 * 但测试环境不安装该包，因此这里提供一个最小实现，避免连接真实 Redis。
 */
if (!class_exists(Redis::class, false)) {
    class Redis
    {
        /**
         * @param string $name 连接名
         * @return object 带 client() 方法的连接对象
         */
        public static function connection(string $name = 'default')
        {
            return new class {
                /**
                 * 返回一个未连接的 phpredis 客户端，仅用于满足
                 * Symfony RedisAdapter 的构造签名。
                 *
                 * @return \Redis
                 */
                public function client(): \Redis
                {
                    return new \Redis();
                }
            };
        }

        /**
         * @return Redis
         */
        public static function instance()
        {
            return new self();
        }
    }
}
