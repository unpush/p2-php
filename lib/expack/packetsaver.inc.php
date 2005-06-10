<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2機能拡張パック - パケット節約関数

/**
 * 設定
 */

// 変換対象外のスクリプト名
// 値がプリセットされているフォームや画像（バイナリ）をするスクリプト
$GLOBALS['ps_ignore_files'] = array('post.php', 'post_form.php', 'read_copy_k.php', 'imgcache.php', 'ic2.php', 'ic2_mkthumb.php', 'tgrepc.php');

// Tidyの設定
$GLOBALS['ps_tidy_config'] = array(
    'doctype' => 'omit',
    'drop-empty-paras' => TRUE,
    'indent' => FALSE,
    'newline' => 'LF',
    'output-bom' => FALSE,
    'output-xhtml' => FALSE,
    'wrap' => 0
);

// Tidyの文字コード
// TidyはシフトJISも処理できるが、シフトJISのまま $GLOBALS['ps_tidy_encoding'] = 'shiftjis' で
// tidy_repair_stringにかけると、なぜか新着まとめ読みが正しく表示できないのでUTF-8にしてから処理
$GLOBALS['ps_tidy_encoding'] = 'utf8';

// 前後のスペースを除去してもレンダリング結果が変わらないHTML要素の正規表現
$GLOBALS['ps_clean_tags'] = 'html|head|meta|title|link|script|style|body|h[1-6]|p|div|address|blockquote|form|fieldset|legend|optgroup|option|ul|ol|li|dl|dt|dd|table|caption|thead|tbody|tfoot|tr|th|td|center|hr|br';


/**
 * パケット節約関数
 */
function packet_saver($buffer)
{
    global $ps_ignore_files;

    $script = basename($_SERVER['SCRIPT_NAME']);
    if (in_array($script, $ps_ignore_files) || defined('P2_NO_SAVE_PACKET')) {
        return $buffer;
    }

    // 正規表現に確実にマッチするように、UTF-8に変換
    $buffer = mb_convert_encoding($buffer, 'UTF-8', 'SJIS-win');

    // HTMLソース最適化関数で処理
    if (extension_loaded('tidy')) {
        global $ps_tidy_config, $ps_tidy_encoding;
        if (version_compare(phpversion(), '5.0.0', 'ge')) {
            $buffer = packet_saver_tidy2($buffer, $ps_tidy_config, $ps_tidy_encoding);
            //$buffer .= '<!-- Tidy/PHP5 -->';
        } else {
            $buffer = packet_saver_tidy($buffer, $ps_tidy_config, $ps_tidy_encoding);
            //$buffer .= '<!-- Tidy/PHP4 -->';
        }
        // meta要素でcharsetを指定しているときは直してくれるんだけど、この場合は余計なお世話
        /*$buffer = preg_replace(
            '/<meta http-equiv="Content-Type" content="text\/html; ?charset=utf-8">/u',
            '<meta http-equiv="Content-Type" content="text/html; charset=shift_jis">',
            $buffer);*/
    } else {
        global $ps_clean_tags;
        $buffer = packet_saver_preg($buffer, $ps_clean_tags);
        //$buffer .= '<!-- PCRE -->';
    }
    // 携帯はSJIS決め打ちなのでcharsetいらない
    $buffer = preg_replace('/<meta http-equiv="Content-Type" content="text\/html; ?charset=\w+">/u', '', $buffer);

    // 一部全角記号を半角の実体参照に変換
    $zenkakuSpChars = array('/＆/u', '/＜/u', '/＞/u');
    $hankakuSpChars = array('&amp;', '&lt;', '&gt;');
    mb_convert_variables('UTF-8', 'SJIS-win', $zenkakuSpChars);
    $buffer = preg_replace($zenkakuSpChars, $hankakuSpChars, $buffer);
    // 全角英数字・カタカナを半角に変換
    $buffer = mb_convert_kana($buffer, 'ka', 'UTF-8');

    //↓jigアプリを利用していて、リンクがおかしくなる場合はコメントアウトを解除する
    //$buffer = preg_replace_callback('/<a ([^<>]+ )?href="([^"]+)"/u', 'jig_unhtmlspecialchars', $buffer);

    // SJISに戻す
    $buffer = mb_convert_encoding($buffer, 'SJIS-win', 'UTF-8');

    return $buffer;
}


/**
 * preg_replace()を使って不要なインデントを除去する
 *
 * 連続するホワイトスペースをまとめ、ブロックレベル要素や<br>など前後のスペースを削っても
 * レンダリング結果が変わらないものは前後のスペースを除去する
 * pre要素の中身まで対象になってしまうけど、ユビキタスでは使っていないので気にしない
 */
function packet_saver_preg($buffer, $clean_tags)
{
    $buffer = preg_replace('/\s+/u', ' ', $buffer);
    $buffer = preg_replace('/ (<\/?('.$clean_tags.')( [^<>]*)?>)/u', '$1', $buffer);
    $buffer = preg_replace('/(<\/?('.$clean_tags.')( [^<>]*)?>) /u', '$1', $buffer);
    return $buffer;
}


/**
 * Tidyを使ってHTMLソースを最適化する (PHP4 - PECL tidy [1.x])
 */
function packet_saver_tidy($buffer, $config, $encoding)
{
    tidy_set_encoding($encoding);
    foreach ($config as $key => $value) {
        tidy_setopt($key, $value);
    }
    $buffer = tidy_repair_string($buffer);
    $buffer = str_replace("\n", '', $buffer);
    return $buffer;
}


/**
 * Tidyを使ってHTMLソースを最適化する (PHP5 - ext/tidy [2.x])
 */
function packet_saver_tidy2($buffer, $config, $encoding)
{
    $buffer = tidy_repair_string($buffer, $config, $encoding);
    $buffer = str_replace("\n", '', $buffer);
    return $buffer;
}


/**
 * jig応急措置のコールバック関数
 *
 * jigのプロキシサーバはクエリ文字列中の&を強制的に&amp;にするらしく、
 * そのままでは&amp;が&amp;amp;にされてGETで値を正しく渡せなくなるので&amp;を&に戻す
 */
function jig_unhtmlspecialchars($amp)
{
    $link = '<a ' . $amp[1] . 'href="' . str_replace('&amp;', '&', $amp[2]) . '"';
    return $link;
}

?>
