<?php

// {{{ P2KeyValueStore_Codec_ShiftJIS

/**
 * Shift_JIS (SJIS-win=CP932) の文字列をUTF-8に変換・復元するCodec
 */
class P2KeyValueStore_Codec_ShiftJIS implements P2KeyValueStore_Codec_Interface
{
    // {{{ _encode()

    /**
     * Shift_JISの文字列をUTF-8に変換する
     *
     * @param string $str
     * @return string
     */
    static private function _encode($str)
    {
        return mb_convert_encoding($str, 'UTF-8', 'SJIS-win');
    }

    // }}}
    // {{{ _decode()

    /**
     * UTF-8の文字列をShift_JISに変換する
     *
     * @param string $str
     * @return string
     */
    static private function _decode($str)
    {
        return mb_convert_encoding($str, 'SJIS-win', 'UTF-8');
    }

    // }}}
    // {{{ encodeKey()

    /**
     * キーをエンコードする
     *
     * @param string $key
     * @return string
     */
    public function encodeKey($key)
    {
        return self::_encode($key);
    }

    // }}}
    // {{{ decodeKey()

    /**
     * キーをデコードする
     *
     * @param string $key
     * @return string
     */
    public function decodeKey($key)
    {
        return self::_decode($key);
    }

    // }}}
    // {{{ encodeValue()

    /**
     * 値をエンコードする
     *
     * @param string $value
     * @return string
     */
    public function encodeValue($value)
    {
        return self::_encode($value);
    }

    // }}}
    // {{{ decodeValue()

    /**
     * 値をデコードする
     *
     * @param string $value
     * @return string
     */
    public function decodeValue($value)
    {
        return self::_decode($value);
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
