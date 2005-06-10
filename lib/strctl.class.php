<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - StrCtl -- 文字列操作クラス
*/

//define('P2_MBREGEX_AVAILABLE_TEST', 0);

class StrCtl{

    /**
     * フォームから送られてきたワードをマッチ関数に適合させる
     *
     * @return string $word_fm 適合パターン。SJISで返す。
     */
    function wordForMatch($word, $method = '')
    {
        $word_fm = $word;

        // 「そのまま」でなければ、全角空白を半角空白に矯正
        if ($method != 'just') {
            $word_fm = mb_convert_kana($word_fm, 's');
        }

        // 正規表現でSJISの2バイト文字を扱うのは何かと問題が多いので、UTF-8にして処理
        $word_fm = mb_convert_encoding($word_fm, 'UTF-8', 'SJIS-win');

        $word_fm = trim($word_fm);
        $word_fm = htmlspecialchars($word_fm, ENT_NOQUOTES);

        // 「正規表現」でなければ、正規表現の特殊文字をエスケープ
        if (in_array($method, array('and', 'or', 'just'))) {
            if (P2_MBREGEX_AVAILABLE == 1) {
                $word_fm = preg_quote($word_fm);
            } else {
                $word_fm = preg_quote($word_fm, '/');
            }

        // 「正規表現」なら
        } else {
            if (P2_MBREGEX_AVAILABLE == 0) {
                $word_fm = preg_replace('/\\//u', '\\/', $word_fm);
            }
        }

        $word_fm = mb_convert_encoding($word_fm, 'SJIS-win', 'UTF-8');

        return $word_fm;
    }

    /**
     * パターンををマッチング用に最適化し、スタティック変数にキャッシュする
     */
    function patternForMultiMatch($pattern)
    {
        static $patterns = array();

        $key = $pattern;
        if (isset($patterns[$key])) {
            return $patterns[$key];
        }

        if (P2_MBREGEX_AVAILABLE == 0) {
            $pattern = mb_convert_encoding($pattern, 'UTF-8', 'SJIS-win');
            $encoding = 'UTF-8';
        } else {
            $encoding = 'SJIS-win';
        }

        // 大文字をすべて小文字にする
        // ※これで大文字/小文字の区別なくマッチするわけではない...
        $pattern = mb_strtolower($pattern, $encoding);

        // 全角/半角を（ある程度）区別なくマッチ
        $_patterns = array();
        $_patterns[0] = $pattern;
        $_patterns[1] = mb_convert_kana($pattern, 'rnKV', $encoding); // 数字とアルファベットは半角、カタカナは全角
        $_patterns[2] = mb_convert_kana($pattern, 'rnk',  $encoding); // 全て半角
        $_patterns[3] = mb_convert_kana($pattern, 'RNKV', $encoding); // 全て全角
        //$_patterns[4] = mb_convert_kana($_patterns[2], 'rnKV', $encoding); // 全角カタカナ+濁点をまとめる(1)
        //$_patterns[5] = mb_convert_kana($_patterns[2], 'RNKV', $encoding); // 全角カタカナ+濁点をまとめる(2)
        $pattern = implode('|', array_unique($_patterns));

        // HTML要素にマッチさせないための否定先読みパターンを付ける
        // 先読みパターンはマッチ結果に含まれないので、$0と$1には同じ文字列がキャプチャされる
        $pattern = '(' . $pattern . ')(?![^<]*>)';

        // 前後をスラッシュ(正規表現デリミタ)で囲み、i(PCRE_CASELESS)修飾子とu(PCRE_UTF8)修飾子を付ける
        if (P2_MBREGEX_AVAILABLE == 0) {
            $pattern = '/' . $pattern . '/iu';
        }

        $patterns[$key] = $pattern;
        return $pattern;
    }

    /**
     * マルチバイト対応で正規表現マッチする
     *
     * @param string $pattern マッチ文字列。P2_MBREGEX_AVAILABLEが1ならSJIS、0ならUTF-8で入ってくる。
     * @param string $target 検索対象文字列。SJISで入ってくる。
     *
     * @return boolean
     */
    function filterMatch($pattern, &$target)
    {
        $pattern = StrCtl::patternForMultiMatch($pattern);

        if (P2_MBREGEX_AVAILABLE ==1) {
            $result = @mb_eregi($pattern, $target);
        } else {
            $utf8txt = mb_convert_encoding($target, 'UTF-8', 'SJIS-win');
            $result = @preg_match($pattern, $utf8txt);
        }

        return (boolean)$result;
    }

    /**
     * マルチバイト対応でマーキングする
     *
     * @param string $pattern マッチ文字列。P2_MBREGEX_AVAILABLEが1ならSJIS、0ならUTF-8で入ってくる。
     * @param string $target 置換対象文字列。SJISで入ってくる。
     *
     * @retun string $result 置換済み文字列
     */
    function filterMarking($pattern, &$target, $marker = '<b class="filtering">\\1</b>')
    {
        $pattern = StrCtl::patternForMultiMatch($pattern);

        if (P2_MBREGEX_AVAILABLE ==1) {
            $result = @mb_eregi_replace($pattern, $marker, $target);
        } else {
            $utf8txt = mb_convert_encoding($target, 'UTF-8', 'SJIS-win');
            $result = @preg_replace($pattern, $marker, $utf8txt);
            $result = mb_convert_encoding($result, 'SJIS-win', 'UTF-8');
        }

        if ($result === FALSE) {
            return $target;
        }
        return $result;
    }

}

?>
