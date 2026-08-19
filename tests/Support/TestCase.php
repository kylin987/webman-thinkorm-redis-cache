<?php

declare(strict_types=1);

namespace Kylin987\ThinkOrm\RedisCache\Tests\Support;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use think\Container;
use Webman\Config;

abstract class TestCase extends PhpUnitTestCase
{
    /**
     * 把给定的配置数组写入临时目录并加载进 Webman\Config，
     * 模拟一个 webman 项目的 config 目录结构。
     *
     * @param array<string, array> $files 相对 config 目录的路径 => 返回的数组
     */
    protected function loadConfig(array $files): void
    {
        $dir = sys_get_temp_dir() . '/webman-thinkorm-redis-cache-' . uniqid();
        mkdir($dir, 0777, true);

        // webman 的 Config::loadFromDir 要求每个配置文件所在目录存在 app.php，
        // 顶层目录也必须存在 app.php 才会扫描 thinkorm.php / bootstrap.php 等文件。
        if (!isset($files['app.php'])) {
            $files['app.php'] = ['enable' => true];
        }

        foreach ($files as $path => $content) {
            $full = $dir . '/' . $path;
            $parent = dirname($full);
            if (!is_dir($parent)) {
                mkdir($parent, 0777, true);
            }
            file_put_contents($full, "<?php\nreturn " . var_export($content, true) . ";\n");
        }

        Config::clear();
        Config::load($dir);
    }

    protected function tearDown(): void
    {
        Config::clear();
        // 清理容器中可能绑定的 think\DbManager 替身
        Container::getInstance()->delete('think\DbManager');
        parent::tearDown();
    }
}
