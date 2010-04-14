<?php

// {{{ P2KeyValueStore_FunctionCache

/**
 * P2KeyValueStoreを使う関数呼び出しキャッシュ
 *
 * 変数を参照で受け取って書き換える関数はうまく動作しない。
 */
class P2KeyValueStore_FunctionCache
{
    // {{{ properties

    /**
     * P2KeyValueStoreオブジェクト
     *
     * @var P2KeyValueStore
     */
    private $_kvs;

    /**
     * キャッシュの有効時間
     *
     * @var int
     */
    private $_lifeTime;

    // }}}
    // {{{ __construct()

    /**
     * コンストラクタ
     *
     * @param P2KeyValueStore $kvs
     *  通常はSerializing Codecを使うことを想定しているが、文字列を返す関数しか
     *  扱わないならCompressing CodecやDefault Codecを使った方が効率が良い。
     * @param int $lifeTime
     */
    public function __construct(P2KeyValueStore $kvs, $lifeTime = -1)
    {
        $this->_kvs = $kvs;
        $this->_lifeTime = $lifeTime;
    }

    // }}}
    // {{{ createProxy()

    /**
     * 関数名を指定して呼び出しプロキシオブジェクトを生成する
     *
     * P2KeyValueStore_FunctionCache_Proxyは__invoke()メソッドを実装しており
     * 可変関数やクロージャのように $proxy($parameter, ...) と呼び出せる。
     * (PHP 5.3以降の場合)
     *
     * @param callable $function
     * @return P2KeyValueStore_FunctionCache_Proxy
     * @throws InvalidArgumentException
     * @see P2KeyValueStore_FunctionCache_Proxy::__construct()
     */
    public function createProxy($function)
    {
        $proxy = new P2KeyValueStore_FunctionCache_Proxy($this, $function);
        $proxy->setLifeTime($this->_lifeTime);
        return $proxy;
    }

    // }}}
    // {{{ invoke()

    /**
     * 関数を呼び出す
     *
     * 関数名と引数から決定されるキーに対応する値がKVSにキャッシュされていれば
     * それを返し、なければ関数を呼び出し、結果をKVSにキャッシュする。
     *
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
     * キャッシュの有効時間を設定する。
     *
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
