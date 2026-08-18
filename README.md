# 基于webman的跨项目共享数据缓存的composer包
此包仅适用于webman框架+thinkorm使用，同时提供适用于thinkphp6的包[点这里](https://github.com/kylin987/think-orm-redis-cache)

## 安装
```
composer require kylin987/webman-thinkorm-redis-cache
```

## 使用
### 1、引入：
```
参考test/model/User.php和Mini.php文件
```
### 2、配置：

**第 1 步：插件配置（自动生成，无需手动新建）**

composer 安装本包时，会自动把内置配置拷贝到
`config/plugin/kylin987/webman-thinkorm-redis-cache/` 目录，webman 会自动加载，目录结构：

- `app.php`：是否启用本插件（开关）
- `config.php`：具体配置参数
- `bootstrap.php`：启动项（自动注册）

其中 `config.php` 默认内容：

```php
return [
    // 数据库缓存 store，需与 config/redis.php 里的连接名一致
    'cache_store'   => 'ormCache',
    // 缓存时间（秒）
    'cache_exptime' => 172800,
    // 空数据是否仍然缓存
    'cache_always'  => true,
];
```

如需修改默认值，直接编辑
`config/plugin/kylin987/webman-thinkorm-redis-cache/config.php` 即可。

**第 2 步：配置 redis 缓存连接**

`config.php` 里的 `cache_store`（默认 `ormCache`）必须对应 `config/redis.php` 里的一个连接名。
在 `config/redis.php` 中新增名为 `ormCache` 的连接：

```php
// orm 缓存专用 redis 连接（连接名需与上面的 cache_store 一致）
'ormCache' => [
    'host'     => '127.0.0.1',
    'password' => '123456',
    'port'     => 6379,
    'database' => 5,   // 建议用独立库，与业务缓存隔离
],
```

> 注意：连接名必须与 `cache_store` 保持一致。若想改用其它连接名（如 `default`），
> 需同步把 `config.php` 里的 `cache_store` 改成同一个名字。

**第 3 步：启动项（自动注册，无需手动操作）**

本包通过插件 `bootstrap.php` 自动注册启动类，进程启动时会自动给 think-orm 注入 redis 缓存，
无需再复制启动文件或修改 `config/bootstrap.php`。

### 升级迁移（老项目兼容）

本包从旧版配置（手动改 `config/thinkorm.php` + 复制 `support/boot/ThinkOrm.php` +
在 `config/bootstrap.php` 注册 `\support\boot\ThinkOrm::class`）迁移到插件配置体系时，
采用「增量迁移」，不会破坏老项目的运行。

**兼容优先级**

配置读取顺序为：`config('thinkorm.cache_*')` 优先，旧值不存在时才读取插件配置。
因此老项目升级后，即使不做任何迁移，也会继续使用原来的 Redis 连接与 TTL；
新项目则直接使用插件配置。

**迁移步骤（可选，让老项目切到插件配置体系）**

1. 删除 `config/bootstrap.php` 中的 `\support\boot\ThinkOrm::class` 一行；
2. 删除 `support/boot/ThinkOrm.php` 文件；
3. 把 `config/thinkorm.php` 中的 `cache_store` / `cache_exptime` / `cache_always` 三项
   复制到 `config/plugin/kylin987/webman-thinkorm-redis-cache/config.php`；
4. 从 `config/thinkorm.php` 中删除这三项（可选，删除后即完全切到插件配置）。

完成以上步骤后，插件 Bootstrap 会自动接管缓存注入。

> 说明：`app.php` 中的 `enable` 开关同样有效，设为 `false` 即可整体停用本插件的配置与启动项，
> 此时若仍保留旧版 `thinkorm.*` 配置，则继续走旧逻辑。

### 3、使用：
```
//获取数据
$id = 10;
$user = User::getRedisCache($id);

//更新数据
//正常使用模型更新数据即可，也可以手动清理缓存触发后续的更新缓存
$id = 10;
$user = User::getRedisCache($id);
User::delCache($user);
```