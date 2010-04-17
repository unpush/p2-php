<?php

// {{{ P2KeyValueStore_Codec_Interface

/**
 * P2KeyValueStoreが使うCodecのインターフェイス定義
 */
interface P2KeyValueStore_Codec_Interface
{
    // {{{ encodeKey()

    /**
     * キーをUTF-8 (or US-ASCII) 文字列にエンコードする
     *
     * @param string $key
     * @return string
     */
    public function encodeKey($key);

    // }}}
    // {{{ decodeKey()

    /**
     * キーをデコードする
     *
     * @param string $key
     * @return string
     */
    public function decodeKey($key);

    // }}}
    // {{{ encodeValue()

    /**
     * 値をUTF-8 (or US-ASCII) 文字列にエンコードする
     *
     * @param string $value
     * @return string
     */
    public function encodeValue($value);

    // }}}
    // {{{ decodeValue()

    /**
     * 値をデコードする
     *
     * @param string $value
     * @return string
     */
    public function decodeValue($value);

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
