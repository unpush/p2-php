<?php

// {{{ P2KeyValueStore_Codec_Array

/**
 * 配列をシリアライズ・アンシリアライズするCodec
 *
 * 実際は非圧縮シリアライズCodecなので配列以外にも対応している。
 *
 * シリアライズ後のサイズが圧縮を必要とするほど大きくない場合に使う。
 * 配列の要素に文字列を含む場合、妥当なUTF-8シーケンスでなければならない。
 */
class P2KeyValueStore_Codec_Array implements P2KeyValueStore_Codec_Interface
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
     * 値をシリアライズする
     *
     * @param array $array
     * @return string
     */
    public function encodeValue($array)
    {
        return serialize($array);
    }

    // }}}
    // {{{ decodeValue()

    /**
     * 値をアンシリアライズする
     *
     * @param string $value
     * @return array
     */
    public function decodeValue($value)
    {
        return unserialize($value);
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
