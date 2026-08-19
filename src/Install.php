<?php

namespace Kylin987\ThinkOrm\RedisCache;

/**
 * webman 基础插件安装器。
 *
 * 通过 WEBMAN_PLUGIN 常量被 webman 自动识别，在 composer install 时
 * 将本包自带的 config/plugin 配置拷贝到主项目的 config/plugin 目录，
 * webman 会自动加载，从而无需手动新建配置文件。
 */
class Install
{
    const WEBMAN_PLUGIN = true;

    /**
     * @var array 源目录（相对 __DIR__） => 目标目录（相对主项目根目录 base_path()）
     */
    protected static $pathRelation = [
        'config/plugin/kylin987/webman-thinkorm-redis-cache' => 'config/plugin/kylin987/webman-thinkorm-redis-cache',
    ];

    /**
     * Install
     * @return void
     */
    public static function install()
    {
        static::installByRelation();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall()
    {
        self::uninstallByRelation();
    }

    /**
     * installByRelation
     * @return void
     */
    public static function installByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            if ($pos = strrpos($dest, '/')) {
                $parent_dir = base_path() . '/' . substr($dest, 0, $pos);
                if (!is_dir($parent_dir)) {
                    mkdir($parent_dir, 0777, true);
                }
            }
            copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
        }
    }

    /**
     * uninstallByRelation
     * @return void
     */
    public static function uninstallByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            $path = base_path() . '/' . $dest;
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }
            remove_dir($path);
        }
    }
}
