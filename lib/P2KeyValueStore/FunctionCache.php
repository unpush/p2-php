<?php

// {{{ P2KeyValueStore_FunctionCache

/**
 * P2KeyValueStoreを使う関数呼び出しキャッシュ
 */
class P2KeyValueStore_FunctionCache
{
    // {{{ properties

    private $_kvs;
    private $_lifeTime;

    // }}}
    // {{{ __construct()

    /**
     * @param P2KeyValueStore $kvs
     * @param callable $function
     */
    public function __construct(P2KeyValueStore $kvs)
    {
        $this->_kvs = $kvs;
        $this->_lifeTime = -1;
    }

    // }}}
    // {{{ getProxy()

    /**
     * @param callable $function
     * @return P2KeyValueStore_FunctionCache_Proxy
     * @throws InvalidArgumentException
     */
    public function getProxy($function)
    {
        $proxy = new P2KeyValueStore_FunctionCache_Proxy($this, $function);
        $proxy->setLifeTime($this->_lifeTime);
        return $proxy;
    }

    // }}}
    // {{{ invoke()

    /**
     * @param callable $function
     * @param array $parameters
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function invoke($function, array $parameters = array())
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException('Non-callable value was given');
        }

        // 関数名
        if (is_string($function)) {
            $name = $function;
            if (strpos($function, '::') !== false) {
                $function = explode('::', $function, 2);
            }
        } elseif (is_object($function)) {
            $name = get_class($function) . '->__invoke';
        } elseif (is_object($function[0])) {
            $name = get_class($function[0]) . '->' . $function[1];
        } else {
            $name = $function[0] . '::' . $function[1];
        }

        // キー
        $key = strtolower($name) . '(';
        if ($n = count($parameters)) {
            $key .= $n . ':' . md5(serialize($parameters));
        } else {
            $key .= 'void';
        }
        $key .= ')';

        // キャッシュを取得
        $record = $this->_kvs->getRaw($key);
        if ($record && !$record->isExpired($this->_lifeTime)) {
            return $this->_kvs->getCodec()->decodeValue($record->value);
        }

        // なければ関数を実行
        if ($n) {
            if ($n == 1 && !is_array($function)) {
                $value = $function(reset($parameters));
            } else {
                $value = call_user_func_array($function, $parameters);
            }
        } elseif (is_array($function)) {
            $value = call_user_func($function);
        } else {
            $value = $function();
        }

        // キャッシュに保存
        if ($record) {
            $this->_kvs->update($key, $value);
        } else {
            $this->_kvs->set($key, $value);
        }

        return $value;
    }

    // }}}
    // {{{ setLifeTime()

    /**
     * @param int $lifeTime
     * @return int
     */
    public function setLifeTime($lifeTime = -1)
    {
        $oldLifeTime = $this->_lifeTime;
        $this->_lifeTime = $lifeTime;
        return $oldLifeTime;
    }

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
