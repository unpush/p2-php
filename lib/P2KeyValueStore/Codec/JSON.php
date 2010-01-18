<?php

// {{{ P2KeyValueStore_Codec_JSON

/**
 * 値をJSONエンコード・デコードするCodec
 *
 * 文字列は妥当なUTF-8シーケンスでなければならない。
 */
class P2KeyValueStore_Codec_JSON implements P2KeyValueStore_Codec_Interface
{
    // {{{ encodeKey()

    /**
     * キーはそのまま
     *
     * @param string $key
     * @return string
     */
    public function encodeKey($key)
    {
        return $key;
    }

    // }}}
    // {{{ decodeKey()

    /**
     * キーはそのまま
     *
     * @param string $key
     * @return string
     */
    public function decodeKey($key)
    {
        return $key;
    }

    // }}}
    // {{{ encodeValue()

    /**
     * 値をJSONエンコードする
     *
     * @param mixed $value
     * @return string
     */
    public function encodeValue($value)
    {
        return json_encode($value);
    }

    // }}}
    // {{{ decodeValue()

    /**
     * 値をJSONデコードする
     *
     * JSONのオブジェクトはstdClassオブジェクトではなく
     * 連想配列に変換する
     *
     * @param string $json
     * @return mixed
     */
    public function decodeValue($json)
    {
        return json_decode($json, true);
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
