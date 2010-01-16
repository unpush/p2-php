<?php

// {{{ P2KeyValueStore_FunctionCache

/**
 * P2KeyValueStore_FunctionCacheを使う関数呼び出しプロキシ
 *
 * 変数を参照で受け取って書き換える関数はうまく動作しない。
 *
 * このクラスは__invoke()メソッドを実装しており、PHP 5.3以降では
 * 可変関数やクロージャのように $proxy($parameter, ...) と呼び出せる。
 */
class P2KeyValueStore_FunctionCache_Proxy
{
    // {{{ properties

    /**
     * P2KeyValueStore_FunctionCacheオブジェクト
     *
     * @var P2KeyValueStore_FunctionCache
     */
    private $_cache;

    /**
     * __invoke() で呼び出される関数
     *
     * @var callable
     */
    private $_function;

    /**
     * __invoke() に与え得られた引数の前に付加されるパラメータのリスト
     *
     * @var array
     */
    private $_prependedParameters;

    /**
     * __invoke() に与え得られた引数の後に付加されるパラメータのリスト
     *
     * @var array
     */
    private $_appendedParameters;

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
     * @param P2KeyValueStore_FunctionCache $cache
     * @param callable $function
     * @throws InvalidArgumentException
     */
    public function __construct(P2KeyValueStore_FunctionCache $cache, $function)
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException('Non-callable value was given');
        }

        $this->_cache = $cache;
        if (is_string($function) && strpos($function, '::') !== false) {
            $this->_function = explode('::', $function, 2);
        } else {
            $this->_function = $function;
        }
        $this->_prependedParameters = array();
        $this->_appendedParameters = array();
        $this->_lifeTime = -1;
    }

    // }}}
    // {{{ __invoke()

    /**
     * 関数を呼び出す
     *
     * @param mixed $parameter
     * @param mixed $...
     * @return mixed
     * @see P2KeyValueStore_FunctionCache_Proxy::invoke()
     */
    public function __invoke()
    {
        $parameters = $this->_prependedParameters;
        $arguments = func_get_args();
        foreach ($arguments as $parameter) {
            $parameters[] = $parameter;
        }
        foreach ($this->_appendedParameters as $parameter) {
            $parameters[] = $parameter;
        }

        $oldLifeTime = $this->_cache->setLifeTime($this->_lifeTime);
        $result = $this->_cache->invoke($this->_function, $parameters);
        $this->_cache->setLifeTime($oldLifeTime);

        return $result;
    }

    // }}}
    // {{{ invoke()

    /**
     * __invoke() のエイリアス
     *
     * @param mixed $parameter
     * @param mixed $...
     * @return mixed
     */
    public function invoke()
    {
        $args = func_get_args();
        if (count($args)) {
            return call_user_func_array(array($this, '__invoke'), $args);
        } else {
            return $this->__invoke();
        }
    }

    // }}}
    // {{{ setPrependedParameters()

    /**
     * 自動で前に追加される引数を設定する
     *
     * @param mixed $...
     * @return void
     */
    public function setPrependedParameters()
    {
        $this->_prependedParameters = func_get_args();
    }

    // }}}
    // {{{ setAppendedParameters()

    /**
     * 自動で後に追加される引数を設定する
     *
     * @param mixed $...
     * @return void
     */
    public function setAppendedParameters()
    {
        $this->_appendedParameters = func_get_args();
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
