<?php
/**
 * p2 - 文字列操作クラス
 * スタティックメソッドで利用する
 */
class StrCtl
{
    /**
     * フォームから送られてきたワードをマッチ関数に適合させる
     *
     * @static
     * @access  public
     * @return  string  $word_fm  適合パターン。SJISで返す。
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
     * マルチバイト対応で正規表現マッチ判定する
     *
     * @static
     * @access  public
     * @param   string  $pattern  マッチ文字列。SJISで入ってくる。
     * @param   string  $target   検索対象文字列。SJISで入ってくる。
     * @param   string  $zenhan   全角/半角の区別を完全になくす
     *                           （これをオンにすると、メモリの使用量が倍くらいになる。速度負担はそれほどでもない）
     * @return  boolean           マッチしたらtrueを返す
     */
    function filterMatch($pattern, $target, $zenhan = true)
    {
        if ($zenhan) {
            // 全角/半角を 完全に 区別なくマッチ
            $pattern = StrCtl::getPatternToHan($pattern);
            $target = StrCtl::getPatternToHan($target, true);
            
        } else {
            // 全角/半角を ある程度 区別なくマッチ
            $pattern = StrCtl::getPatternZenHan($pattern);
        }
        
        // HTML要素にマッチさせないための否定先読みパターンを付加
        $pattern = $pattern . '(?![^<]*>)';

        if (P2_MBREGEX_AVAILABLE == 1) {
            $result = @mb_eregi($pattern, $target);    // None|Error:FALSE
        } else {
            // UTF-8に変換してから処理する
            $pattern_utf8 = '/' . mb_convert_encoding($pattern, 'UTF-8', 'SJIS-win') . '/iu';
            $target_utf8 = mb_convert_encoding($target, 'UTF-8', 'SJIS-win');
            $result = preg_match($pattern_utf8, $target_utf8);    // None:0, Error:FALSE
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
     * @static
     * @access  public
     * @param   string  $pattern  マッチ文字列。SJISで入ってくる。
     * @param   string  $target   置換対象文字列。SJISで入ってくる。
     * @return  string
     */
    function filterMarking($pattern, &$target, $marker = '<b class="filtering">\\0</b>')
    {
        // 全角/半角を（ある程度）区別なくマッチ
        $pattern = StrCtl::getPatternZenHan($pattern);

        // HTML要素にマッチさせないための否定先読みパターンを付加
        $pattern = $pattern . '(?![^<]*>)';

        if (P2_MBREGEX_AVAILABLE == 1) {
            $result = mb_eregi_replace($pattern, $marker, $target);    // Error:FALSE
        } else {
            // UTF-8に変換してから処理する
            $pattern_utf8 = '/' . mb_convert_encoding($pattern, 'UTF-8', 'SJIS-win') . '/iu';
            $target_utf8 = mb_convert_encoding($target, 'UTF-8', 'SJIS-win');
            $result = preg_replace($pattern_utf8, $marker, $target_utf8);
            $result = mb_convert_encoding($result, 'SJIS-win', 'UTF-8');
        }

        if ($result === FALSE) {
            return $target;
        }
        return $result;
    }
    
    /**
     * 全角/半角を（ある程度）区別なくパッチするための正規表現パターンを得る
     *
     * @static
     * @access  private
     * @return  string
     */
    function getPatternZenHan($pattern)
    {
        $pattern_han = StrCtl::getPatternToHan($pattern);
        
        if ($pattern != $pattern_han) {
            $pattern = '(?:' . $pattern . '|' . $pattern_han . ')';
        }
        $pattern_zen = StrCtl::getPatternToZen($pattern);
        
        if ($pattern != $pattern_zen) {
            $pattern = '(?:' . $pattern . '|' . $pattern_zen . ')';
        }
        
        return $pattern;
    }
    
    /**
     * （パターン）文字列を半角にする
     *
     * @static
     * @access  private
     * @return  string
     */
    function getPatternToHan($pattern, $no_escape = false)
    {
        $kigou = StrCtl::getKigouPattern($no_escape);
        
        // 壊れる
        //$pattern = str_replace($kigou['zen'], $kigou['han'], $pattern);

        if (P2_MBREGEX_AVAILABLE == 1) {

            foreach ($kigou['zen'] as $k => $v) {
        
                $word_fm = $kigou['zen'][$k];
                
                /*
                // preg_quote()で2バイト目が0x5B("[")の"ー"なども変換されてしまうので
                // UTF-8にしてから正規表現の特殊文字をエスケープ
                $word_fm = mb_convert_encoding($word_fm, 'UTF-8', 'SJIS-win');
                $word_fm = preg_quote($word_fm);
                $word_fm = mb_convert_encoding($word_fm, 'SJIS-win', 'UTF-8');
                */
                
                $pattern = mb_ereg_replace($word_fm, $kigou['han'][$k], $pattern);
            }
        }
        
        //echo $pattern;
        $pattern = mb_convert_kana($pattern, 'rnk');
        
        
        
        return $pattern;
    }
    
    /**
     * （パターン）文字列を全角にする
     *
     * @static
     * @access  private
     * @return  string
     */
    function getPatternToZen($pattern, $no_escape = false)
    {
        $kigou = StrCtl::getKigouPattern($no_escape);
        
        // 壊れる
        // $pattern = str_replace($kigou['han'], $kigou['zen'], $pattern);
        
        if (P2_MBREGEX_AVAILABLE == 1) {
            foreach ($kigou['zen'] as $k => $v) {
        
                $word_fm = $kigou['han'][$k];
                
                
                // preg_quote()で2バイト目が0x5B("[")の"ー"なども変換されてしまうので
                // UTF-8にしてから正規表現の特殊文字をエスケープ
                $word_fm = mb_convert_encoding($word_fm, 'UTF-8', 'SJIS-win');
                $word_fm = preg_quote($word_fm);
                $word_fm = mb_convert_encoding($word_fm, 'SJIS-win', 'UTF-8');
                
                
                $pattern = mb_ereg_replace($word_fm, $kigou['zen'][$k], $pattern);
            }
        }
        
        $pattern = mb_convert_kana($pattern, 'RNKV');
        
        return $pattern;
    }
    
    /**
     * 全角/半角の記号パターンを得る
     *
     * @static
     * @access  private
     * @return  string
     */
    function getKigouPattern($no_escape = false)
    {
        $kigou['zen'] = array('｀', '（', '）', '？', '＃', '＄', '％', '＠', '＜',   '＞', '！',   '＊', '＋', '＆',  '＝', '～', '｜', '｛', '｝', '＿');
        $kigou['han'] = array('`',  '\(', '\)', '\?', '#',  '\$', '%',  '@',  '&lt;', '&gt;', '\!', '\*', '\+', '&amp;', '=', '~', '\|', '\{', '\}', '_');
        
        // NG ---- $ < 
        // str_replace を通した時に、文字が壊れるの回避。。
        //$kigou['zen'] = array('｀', '（', '）', '？', '＃', '％', '＠', '＞', '！',   '＊', '＋', '＆');
        //$kigou['han'] = array('`',  '\(', '\)', '\?', '#',  '%',  '@',  '&gt;', '\!', '\*', '\+', '&amp;');

        if ($no_escape) {
            $kigou['han'] = array_map(create_function('$str', 'return ltrim($str, "\\\");'), $kigou['han']);
            /*
            foreach ($kigou['han'] as $k => $v) {
                $kigou['han'][$k] = ltrim($v, '\\');
            }
            */
        }
        
        return $kigou;
    }
}

