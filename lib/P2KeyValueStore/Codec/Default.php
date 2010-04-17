<?php

// {{{ P2KeyValueStore_Codec_Default

/**
 * 何もしないCodec
 */
class P2KeyValueStore_Codec_Default implements P2KeyValueStore_Codec_Interface
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
     * 値はそのまま
     *
     * @param string $value
     * @return string
     */
    public function encodeValue($value)
    {
        return $value;
    }

    // }}}
    // {{{ decodeValue()

    /**
     * 値はそのまま
     *
     * @param string $value
     * @return string
     */
    public function decodeValue($value)
    {
        return $value;
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
