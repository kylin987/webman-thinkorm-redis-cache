<?php

return [
    //数据库缓存store，对应 config/redis.php 中的连接名
    'cache_store'   => 'ormCache',
    //缓存时间（秒）
    'cache_exptime' => 172800,
    //空数据是否仍然缓存
    'cache_always'  => true,
];
