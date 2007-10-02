<?php
// グローバル関数

/**
 * htmlspecialchars() の別名みたいなもの
 *
 * @param   string  $alt  値が空のときの代替文字列
 * @return  string|null
 */
function hs($str, $alt = '', $quoteStyle = ENT_QUOTES)
{
    return (isset($str) && strlen($str) > 0) ? htmlspecialchars($str, $quoteStyle) : $alt;
}

/**
 * notice の抑制もしてくれる hs()
 * 参照で値を受け取るのはイマイチだが、そうしなければnoticeの抑制ができない
 *
 * @param   &string  $str  文字列変数の参照
 * @return  string|null
 */
function hsi(&$str, $alt = '', $quoteStyle = ENT_QUOTES)
{
    return (isset($str) && strlen($str) > 0) ? htmlspecialchars($str, $quoteStyle) : $alt;
}

/**
 * echo hs()
 *
 * @return  void
 */
function eh($str, $alt = '', $quoteStyle = ENT_QUOTES)
{
    echo hs($str, $alt, $quoteStyle);
}

/**
 * echo hs() （noticeを抑制する）
 *
 * @param   &string  $str  文字列変数の参照
 * @return  void
 */
function ehi(&$str, $alt = '', $quoteStyle = ENT_QUOTES)
{
    echo hs($str, $alt, $quoteStyle);
}

/**
 * 存在しない変数の notice を出すことなく、変数の値を取得する
 *
 * @return  mixed
 */
function geti(&$var, $alt = null)
{
    return isset($var) ? $var : $alt;
}

/**
 * 改行を付けて文字列を出力する。cli(\n)とweb(<br>)で出力が変化する。
 * 引数の文字列は複数取ることが可能。引数がなければ改行だけを出力する。
 *
 * @return  void
 */
function echoln()
{
    $n = (php_sapi_name() == 'cli') ? "\n" : '<br>';
    
    if ($args = func_get_args()) {
        foreach ($args as $v) {
            echo $v . $n;
        }
    } else {
        echo $n;
    }
}

/**
 * p2 error メッセージを表示して終了
 *
 * @param   string  $err    エラー概要
 * @param   string  $msg    詳細な説明
 * @param   boolean $raw    詳細な説明をエスケープするならtrue
 * @return  void
 */
function p2die($err, $msg = null, $raw = false)
{
    echo '<html><head><title>p2 error</title></head><body>';
    printf('<h4>p2 error: %s</h4>', htmlspecialchars($err, ENT_QUOTES));
    if ($msg !== null) {
        if ($raw) {
            printf('<p>%s</p>', nl2br(htmlspecialchars($msg, ENT_QUOTES)));
        } else {
            echo $msg;
        }
    }
    echo '</body></html>';
    
    exit;
}
