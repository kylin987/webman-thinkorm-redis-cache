<?php

namespace Kylin987\ThinkOrm\RedisCache\traits;

use support\Redis;

trait ThinkOrmCache
{
    //获取主键缓存
    public static function getRedisCache($id, $getDb = false)
    {
        list($key, $pk, $cacheExpTime) = self::getCacheKey(self::getModel(), $id);
        if ($getDb) {
            self::delKey($key);
        }
        $always = config('thinkorm.cache_always') ?? true;
        $res = self::cache($key, $cacheExpTime, null, $always)->where($pk, '=', $id)->find();
        return $res;
    }

    /**
     * @param $id
     * @param $option ['cachePk' => '自定义主键']
     * @param $getDb
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getRedisCacheOption($id, $option = [], $getDb = false)
    {
        list($key, $pk, $cacheExpTime) = self::getCacheKey(self::getModel(), $id, $option);
        if ($getDb) {
            self::delKey($key);
        }
        $always = config('database.cache_always') ?? true;
        $res = self::cache($key, $cacheExpTime, null, $always)->where($pk, '=', $id)->find();
        return $res;
    }

    //删除缓存
    public static function delCache($model)
    {
        list($key) = self::getCacheKey($model);
        return self::delKey($key);
    }
    
    //redis删除
    private static function delKey($key)
    {
        $connection = config('thinkorm.cache_store') ?? 'default';
        return Redis::instance()->connection($connection)->client()->del($key);
    }

    //获取redis键和主pk
    private static function getCacheKey($model, $id = null)
    {
        $pk = $model->cachePk ?? 'id';
        if (isset($option['cachePk']) && !empty($option['cachePk'])) {
            $pk = $option['cachePk'];
        }
        $cacheExpTime = $model->cacheExpTime ?? config('thinkorm.cache_exptime');
        return ['orm_' . $model->getTable() . '_' . $pk . '_' . (is_null($id) ? $model->$pk : $id), $pk, $cacheExpTime];
    }
}