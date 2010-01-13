<?php

// {{{ P2KeyValueStore_Codec_Compressing

/**
 * サイズの大きいデータを圧縮するCodec
 */
class P2KeyValueStore_Codec_Compressing extends P2KeyValueStore_Codec_Binary
{
    // {{{ encodeValue()

    /**
     * データを圧縮する
     *
     * @param string $value
     * @return string
     */
    public function encodeValue($value)
    {
        return parent::encodeValue(gzdeflate($value, 6));
    }

    // }}}
    // {{{ decodeValue()

    /**
     * データを展開する
     *
     * @param string $value
     * @return string
     */
    public function decodeValue($value)
    {
        return gzinflate(parent::decodeValue($value));
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
