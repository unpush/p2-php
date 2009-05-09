<?php
require_once dirname(__FILE__) . '/CompressingStore.php';

// {{{ SerializingStore

/**
 * 値をシリアライズして永続化する
 */
class SerializingStore extends CompressingStore
{
    // {{{ _encodeValue()

    /**
     * 値をシリアライズする
     *
     * @param mixed $value
     * @return string
     */
    protected function _encodeValue($value)
    {
        return parent::_encodeValue(serialize($value));
    }

    // }}}
    // {{{ _decodeValue()

    /**
     * 値をアンシリアライズする
     *
     * @param string $value
     * @return mixed
     */
    protected function _decodeValue($value)
    {
        return unserialize(parent::_decodeValue($value));
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
