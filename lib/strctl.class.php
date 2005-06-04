<?php
/*
    p2 - StrCtl -- 文字列操作クラス
*/
class StrCtl{

    /**
     * フォームから送られてきたワードをマッチ関数に適合させる
     *
     * @return string $word_fm 適合パターン。SJISで返す。
     */
    function wordForMatch($word, $method = 'regex')
    {
        $word_fm = $word;
        
        // 「そのまま」でなければ、全角空白を半角空白に矯正
        if ($method != 'just') {
            $word_fm = mb_convert_kana($word_fm, 's');
        }
        
        $word_fm = trim($word_fm);
        $word_fm = htmlspecialchars($word_fm, ENT_NOQUOTES);
        
        if (in_array($method, array('and', 'or', 'just'))) {
            // preg_quote()で2バイト目が0x5B("[")の"ー"なども変換されてしまうので
            // UTF-8にしてから正規表現の特殊文字をエスケープ
            $word_fm = mb_convert_encoding($word_fm, 'UTF-8', 'SJIS-win');
            if (P2_MBREGEX_AVAILABLE == 1) {
                $word_fm = preg_quote($word_fm);
            } else {
                $word_fm = preg_quote($word_fm, '/');
            }
            $word_fm = mb_convert_encoding($word_fm, 'SJIS-win', 'UTF-8');
            
        // 他、regex（正規表現）なら
        } else {
            if (P2_MBREGEX_AVAILABLE == 0) {
                $word_fm = str_replace('/', '\/', $word_fm);
            }
        }
        return $word_fm;
    }

    /**
     * マルチバイト対応で正規表現マッチする
     *
     * @param string $pattern マッチ文字列。SJISで入ってくる。
     * @param string $target 検索対象文字列。SJISで入ってくる。
     */
    function filterMatch($pattern, &$target)
    {
        // 全角/半角を（ある程度）区別なくマッチ
        $pattern_han = mb_convert_kana($pattern, 'rnk');
        if ($pattern != $pattern_han) {
            $pattern = $pattern.'|'.$pattern_han;
        }
        $pattern_zen = mb_convert_kana($pattern, 'RNKV');
        if ($pattern != $pattern_zen) {
            $pattern = $pattern.'|'.$pattern_zen;
        }
        
        // HTML要素にマッチさせないための否定先読みパターンを付加
        $pattern = '(' . $pattern . ')(?![^<]*>)';

        if (P2_MBREGEX_AVAILABLE == 1) {
            $result = @mb_eregi($pattern, $target);
        } else {
            // UTF-8に変換してから処理する
            $pattern_utf8 = '/' . mb_convert_encoding($pattern, 'UTF-8', 'SJIS-win') . '/iu';
            $target_utf8 = mb_convert_encoding($target, 'UTF-8', 'SJIS-win');
            $result = @preg_match($pattern_utf8, $target_utf8);
            //$result = mb_convert_encoding($result, 'SJIS-win', 'UTF-8');
        }
        
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * マルチバイト対応でマーキングする
     *
     * @param string $pattern マッチ文字列。SJISで入ってくる。
     * @param string $target 置換対象文字列。SJISで入ってくる。
     */
    function filterMarking($pattern, &$target, $marker = '<b class="filtering">\\1</b>')
    {
        // 全角/半角を（ある程度）区別なくマッチ
        $pattern_han = mb_convert_kana($pattern, 'rnk');
        if ($pattern != $pattern_han) {
            $pattern = $pattern.'|'.$pattern_han;
        }
        $pattern_zen = mb_convert_kana($pattern, 'RNKV');
        if ($pattern != $pattern_zen) {
            $pattern = $pattern.'|'.$pattern_zen;
        }
        
        // HTML要素にマッチさせないための否定先読みパターンを付加
        $pattern = '(' . $pattern . ')(?![^<]*>)';

        if (P2_MBREGEX_AVAILABLE == 1) {
            $result = @mb_eregi_replace($pattern, $marker, $target);
        } else {
            // UTF-8に変換してから処理する
            $pattern_utf8 = '/' . mb_convert_encoding($pattern, 'UTF-8', 'SJIS-win') . '/iu';
            $target_utf8 = mb_convert_encoding($target, 'UTF-8', 'SJIS-win');
            $result = @preg_replace($pattern_utf8, $marker, $target_utf8);
            $result = mb_convert_encoding($result, 'SJIS-win', 'UTF-8');
        }

        if ($result === FALSE) {
            return $target;
        }
        return $result;
    }
}

?>
