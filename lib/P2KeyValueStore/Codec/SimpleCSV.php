<?php

// {{{ P2KeyValueStore_Codec_SimpleCSV

/**
 * 配列とCSVの相互変換をするCodec
 *
 * 単に値をカンマで implode()/explode() するだけで、データの検証はしない。
 * 配列の全要素はUTF-8 (またはUS-ASCII) の文字列でなければならない。
 */
class P2KeyValueStore_Codec_SimpleCSV implements P2KeyValueStore_Codec_Interface
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
     * 値を結合する
     *
     * @param array $array
     * @return string
     */
    public function encodeValue($array)
    {
        return implode(',', $array);
    }

    // }}}
    // {{{ decodeValue()

    /**
     * 値を分割する
     *
     * @param string $value
     * @return array
     */
    public function decodeValue($value)
    {
        return explode(',', $value);
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
