<?php
namespace Clown;

use Redis;

class Cache
{
    private static $_keyPrefix = 'Clown:';

    public static function connection()
    {
        $config = Config::get('redis');
        $redis = new Redis();
        $redis->connect($config['host'], $config['port']);
        $redis->setOption(Redis::OPT_PREFIX, self::$_keyPrefix);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $redis->select(isset($config['dbIndex']) ? $config['dbIndex'] : 0);
        return $redis;
    }

    public static function get($key)
    {
        return static::connection()->get($key);
    }

    public static function set($key, $value)
    {
        $config = Config::get('redis');

        if (isset($config['expire'])) {
            $expire = $config['expire'];
        } else {
            $expire = 604800; // 60 * 60 * 24 * 7
        }
        return static::connection()->setex($key, $expire, $value);
    }

    public static function delete($key)
    {
        return static::connection()->delete($key);
    }
}