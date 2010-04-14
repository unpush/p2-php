<?php

// {{{ P2KeyValueStore_Codec_ArrayShiftJIS

/**
 * 配列をシリアライズ・アンシリアライズするCodec
 *
 * 実際は非圧縮シリアライズCodecなので配列以外にも対応している。
 *
 * シリアライズ後のサイズが圧縮を必要とするほど大きくない場合に使う。
 * 配列の要素に含まれる文字列は妥当なShift_JISシーケンスでなければならず、
 * キーは数値かUS-ASCII文字列であることを期待する。
 */
class P2KeyValueStore_Codec_ArrayShiftJIS extends P2KeyValueStore_Codec_Array
{
    // {{{ encodeValue()

    /**
     * 値をUTF-8に変換した後、シリアライズする
     *
     * @param array $array
     * @return string
     */
    public function encodeValue($array)
    {
        mb_convert_variables('UTF-8', 'Shift_JIS', $array);
        return parent::encodeValue($array);
    }

    // }}}
    // {{{ decodeValue()

    /**
     * 値をアンシリアライズした後、Shift_JISに変換する
     *
     * @param string $value
     * @return array
     */
    public function decodeValue($value)
    {
        $array = parent::decodeValue($value);
        mb_convert_variables('SJIS-win', 'UTF-8', $array);
        return $array;
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
