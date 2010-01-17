<?php

// {{{ UrlSafeBase64

/**
 * エスケープせずにURLに埋め込めるBase64変換クラス
 */
class UrlSafeBase64
{
    // {{{ decode()

    /**
     * URL-safe Base64 デコード
     *
     * @param string $str
     * @return string
     */
    static public function decode($str)
    {
        $mod = strlen($str) % 4;
        if ($mod) {
            $str .= str_repeat('=', 4 - $mod);
        }
        return base64_decode(strtr($str, '-_', '+/'), true);
    }

    // }}}
    // {{{ encode()

    /**
     * URL-safe Base64 エンコード
     *
     * @param string $str
     * @return string
     */
    static public function encode($str)
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
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
