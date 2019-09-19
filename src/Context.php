<?php
namespace CloverSwoole\Utils;
use Swoole\Coroutine as SwCoroutine;

/**
 * 上下文管理器
 * Class Context
 * @package CloverSwoole\Utils
 */
class Context
{
    protected static $nonCoContext = [];

    /**
     * 设置值
     * @param string $id
     * @param $value
     * @return mixed
     */
    public static function set(string $id, $value)
    {
        if (Coroutine::inCoroutine()) {
            SwCoroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    /**
     * 获取内容
     * @param string $id
     * @param null $default
     * @param null $coroutineId
     * @return mixed|null
     */
    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return SwCoroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return SwCoroutine::getContext()[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    /**
     * 是否存在
     * @param string $id
     * @param null $coroutineId
     * @return bool
     */
    public static function has(string $id, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            if ($coroutineId !== null) {
                return isset(SwCoroutine::getContext($coroutineId)[$id]);
            }
            return isset(SwCoroutine::getContext()[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * 不在协程环境时 释放上下文
     * @param string $id
     */
    public static function destroy(string $id)
    {
        unset(static::$nonCoContext[$id]);
    }

    /**
     * 将上下文从协程复制到当前协程
     * @param int $fromCoroutineId
     * @param array $keys
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void
    {
        /**
         * @var \ArrayObject
         * @var \ArrayObject $current
         */
        $from = SwCoroutine::getContext($fromCoroutineId);
        $current = SwCoroutine::getContext();
        $current->exchangeArray($keys ? array_fill_keys($keys, $from->getArrayCopy()) : $from->getArrayCopy());
    }

    /**
     * Retrieve the value and override it by closure.
     */
    public static function override(string $id, \Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }

    /**
     * 获取上下文
     * @return array|mixed
     */
    public static function getContainer()
    {
        if (Coroutine::inCoroutine()) {
            return SwCoroutine::getContext();
        }

        return static::$nonCoContext;
    }
}
