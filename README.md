# 基于webman的跨项目共享数据缓存的composer包
此包仅适用于webman框架+thinkorm使用，同时提供适用于thinkphp6的包[点这里](https://github.com/kylin987/think-orm-redis-cache)

## 安装
```
composer require kylin987/think-orm-redis-cache
```

## 使用
### 1、引入：
```
参考test/model/User.php和Mini.php文件
```
### 2、配置：
```
下面的1和2都属于个性配置，可以不配置，走默认redis，php7.4及以下可以不配置第3项

1、修改config/thinkorm.php，添加以下3个配置

//数据库缓存store
'cache_store'       => 'ormCache',
//缓存时间
'cache_exptime' => 172800,
//空数据是否仍然缓存
'cache_always' => true,

2、修改config/redis.php，增加一个缓存store，名字ormCache和上面的配置保持一致，下面的配置根据需求自行配置

// ormredis缓存
'ormCache' => [
    'host' => '127.0.0.1',
    'password' => '123456',
    'port' => 6379,
    'database' => 5,
],

3、添加启动项文件，把test文件夹下的boot文件夹复制到webman根目录下的support文件夹内

```
### 2、使用：
```
//获取数据
$id = 10;
$user = User::getCache($id);

//更新数据
//正常使用模型更新数据即可，也可以手动清理缓存触发后续的更新缓存
$id = 10;
$user = User::getCache($id);
User::delCache($user);
```