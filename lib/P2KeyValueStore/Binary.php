<?php
require_once dirname(__FILE__) . '/../P2KeyValueStore.php';

// {{{ P2KeyValueStore_Binary

/**
 * バイナリデータを永続化する
 */
class P2KeyValueStore_Binary extends P2KeyValueStore
{
    // {{{ encodeValue()

    /**
     * データをBase64エンコードする
     *
     * @param string $value
     * @return string
     */
    public function encodeValue($value)
    {
        return base64_encode($value);
    }

    // }}}
    // {{{ decodeValue()

    /**
     * データをBase64デコードする
     *
     * @param string $value
     * @return string
     */
    public function decodeValue($value)
    {
        return base64_decode($value);
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
