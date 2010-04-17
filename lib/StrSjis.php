<?php
// {{{ StrSjis

/**
 * SJISのためのクラス。スタティックメソッドで利用する。
 * SJIS文字列の末尾が壊れているのを修正カットする。
 *
 * @author aki
 * @since  2006/10/02
 * @static
 */
class StrSjis
{
    // {{{ note

    /**
     * 参考データ
     * SJIS 2バイトの第1バイト範囲 129～159、224～239（0x81～0x9F、0xE0～0xEF）
     * SJIS 2バイトの第2バイト範囲 64～126、128～252（0x40～0x7E、0x80～0xFC）（第1バイト範囲を包括している）
     * SJIS 英数字(ASCII) 33～126（0x21～0x7E） 32 空白
     * SJIS 半角カナ161～223（0xA1～0xDF）(第2バイト領域)
     */

    /*
    // SJIS文字化け文字が直前の第1バイト文字で打ち消されるかどうかの目視用テストコード
    // →打ち消されるが、末尾のみのチェックでは不足。先頭から順に2バイトの組を調べる必要がある…。
    for ($i = 0; $i <= 255; $i++) {
        if (self::isSjis1stByte($i)) {
            for ($j = 0; $j <= 255; $j++) {
                if (self::isSjisCrasherCode($j)) {
                    echo $i . ' '. pack('C*', $i) . pack('C*', $j) . "<br><br>";
                }
            }
        }
    }
    */

    // }}}
    // {{{ fixSjis()

    /**
     * SJIS文字列の末尾が、第一バイトであれば、タグが壊れる要因となるのでカットする。
     *
     * @access  public
     * @return  string
     */
    static public function fixSjis($str)
    {
        if (strlen($str) == 0) {
            return;
        }

        $un = unpack('C*', $str);

        $on_sjisfirst = false;
        $on_crasher = false;
        foreach ($un as $v) {
            if ($on_sjisfirst) {
                $on_sjisfirst = false;
                $on_crasher = false;
            } else {
                if (self::isSjis1stByte($v)) {
                    $on_sjisfirst = true;
                    $on_crasher = true;
                } elseif (self::isSjisCrasherCode($v)) {
                    $on_crasher = true;
                }
            }
        }

        if ($on_crasher) {
            $str = substr($str, 0, -1);
        }
        return $str;

        /*
        // 末尾のみをチェックするためのコード。これでは不足。
        if (self::isSjisCrasherCode($un[$count]) && !self::isSjis1stByte($un[$count-1])) {
            $str = substr($str, 0, -1);
            return $str;
        }
        */
    }

    // }}}
    // {{{ isSjisCrasherCode()

    /**
     * SJISで末尾にあると（続く開始タグとくっついて）文字化けする可能性のあるコードの範囲（10進数）
     * 第1バイト範囲だけでなく第2バイト範囲でも文字化けするコードはある
     * 129-159 224-252 （目視で調べた）
     * 目視用テストコード
     * for ($i = 0; $i <= 255; $i++) {
     *    echo $i . ': '. pack('C*', $i) . "<br><br>";
     * }
     * （参考 SJIS 2バイトコード範囲のうちで1バイトコードに当てはまらないのは 128-160 224-252）
     *
     * @return  boolean  コード番号が文字化け範囲であれば true を返す
     */
    static public function isSjisCrasherCode($int)
    {
        if (129 <= $int && $int <= 159 or 224 <= $int && $int <= 252) {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ isSjis1stByte()

    /**
     * SJIS 2バイトの第1バイト範囲かどうかを調べる 129～159、224～239（0x81～0x9F、0xE0～0xEF）
     *
     * @return  boolean  コード番号が第1バイト範囲であれば true を返す
     */
    static public function isSjis1stByte($int)
    {
        if (129 <= $int && $int <= 159 or 224 <= $int && $int <= 239) {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ getUnicodePattern()

    /**
     * Shift_JISの文字を含む正規表現パターンを
     * PCREのUnicodeモード用正規表現パターンに変換する
     *
     * @param   string  $pattern
     * @return  string
     */
    static public function toUnicodePattern($pattern)
    {
        $sjis_char_class_1st = '[\\x81-\\x9F\\xE0-\\xFC]';
        $sjis_char_class_2nd = '[\\x40-\\x7E\\x80-\\xFC]';
        $sjis_char_regex_1st = '\\\\x(8[1-9A-F]|[9E][0-9A-F]|F[0-9A-C])';
        $sjis_char_regex_2nd = '\\\\x([45689A-E][0-9A-F]|7[0-9A-E]|F[0-9A-C])';

        $pattern = preg_replace_callback("/{$sjis_char_class_1st}{$sjis_char_class_2nd}/",
                                         array(__CLASS__, '_sjisStringToUnicodePatternCb'),
                                         $pattern);
        $pattern = preg_replace_callback("/{$sjis_char_regex_1st}{$sjis_char_regex_2nd}/i",
                                         array(__CLASS__, '_sjisPatternToUnicodePatternCb'),
                                         $pattern);
        return $pattern;
    }

    // }}}
    // {{{ _sjisStringToUnicodePattern()

    /**
     * Shift_JISの2バイト文字を
     * Unicode文字にマッチする正規表現パターンに変換する
     *
     * @param   array   $m
     * @return  string
     */
    static protected function _sjisStringToUnicodePatternCb($m)
    {
        $u = unpack('C2', mb_convert_encoding($m[0], 'UCS-2BE', 'SJIS-win'));
        return sprintf('\\x{%02X%02X}', $u[1], $u[2]);
    }

    // }}}
    // {{{ _sjisCodeToUnicodePattern()

    /**
     * Shift_JISの2バイト文字にマッチする正規表現パターンを
     * Unicode文字にマッチする正規表現パターンに変換する
     *
     * @param   array   $m
     * @return  string
     */
    static protected function _sjisPatternToUnicodePatternCb($m)
    {
        $s = pack('C2', hexdec($m[1]), hexdec($m[2]));
        return self::_sjisStringToUnicodePatternCb(array($s));
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
