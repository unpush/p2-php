<?php

// {{{ P2KeyValueStore_FunctionCache

/**
 * P2KeyValueStore_FunctionCacheを使う関数呼び出しプロキシ
 */
class P2KeyValueStore_FunctionCache_Proxy
{
    // {{{ properties

    private $_cache;
    private $_function;
    private $_prependedParameters;
    private $_appendedParameters;
    private $_lifeTime;

    // }}}
    // {{{ __construct()

    /**
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
        $this->_function = $function;
        $this->_prependedParameters = array();
        $this->_appendedParameters = array();
        $this->_lifeTime = -1;
    }

    // }}}
    // {{{ __invoke()

    /**
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
    // {{{ prependParameters()

    /**
     * @param mixed $...
     * @return void
     */
    public function prependParameters()
    {
        $this->_prependedParameters = func_get_args();
    }

    // }}}
    // {{{ appendParameters()

    /**
     * @param mixed $...
     * @return void
     */
    public function appendParameters()
    {
        $this->_appendedParameters = func_get_args();
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
