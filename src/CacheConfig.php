<?php

declare(strict_types=1);

namespace Kylin987\ThinkOrm\RedisCache;

/**
 * 缓存配置读取入口。
 *
 * 采用「增量迁移」策略，兼容老项目：老项目把 cache_store / cache_exptime /
 * cache_always 写在 config/thinkorm.php 中，升级后这些值优先于插件配置生效，
 * 因此不会因升级切换到新连接或 TTL 而导致缓存错乱；新项目则直接使用插件配置。
 *
 * 插件配置通过 webman 插件机制提供：本包内置
 * src/config/plugin/kylin987/webman-thinkorm-redis-cache/{app.php,config.php}，
 * composer 安装时 Install.php 会自动拷贝到主项目的
 * config/plugin/kylin987/webman-thinkorm-redis-cache/，
 * webman 会自动加载，因此无需手动新建配置文件。
 *
 * 其中 app.php 只含 enable 开关，具体参数写在 config.php，
 * 如需修改默认值，直接编辑主项目中的
 * config/plugin/kylin987/webman-thinkorm-redis-cache/config.php 即可。
 */
class CacheConfig
{
    /**
     * 插件配置命名空间，与 config/plugin 下的目录结构一一对应。
     * 例如 kylin987.webman-thinkorm-redis-cache 对应
     * config/plugin/kylin987/webman-thinkorm-redis-cache/。
     */
    public const PLUGIN_NAME = 'kylin987.webman-thinkorm-redis-cache';

    /**
     * 插件配置文件（不含 .php 后缀），具体配置参数所在文件。
     */
    public const CONFIG_FILE = 'config';

    /**
     * 读取缓存配置项。
     *
     * 兼容优先级：先读取旧配置 config('thinkorm.' . $key)，只有旧值不存在
     * （为 null）时才回退到插件配置。这样老项目升级后继续沿用原 Redis 连接与 TTL，
     * 新项目则直接使用插件配置。
     *
     * @param string $key     配置键，例如 cache_store / cache_exptime / cache_always
     * @param mixed  $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $legacy = config('thinkorm.' . $key);
        if ($legacy !== null) {
            return $legacy;
        }

        return config('plugin.' . self::PLUGIN_NAME . '.' . self::CONFIG_FILE . '.' . $key, $default);
    }
}
