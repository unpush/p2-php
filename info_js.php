<?php
/**
 * rep2 - 板・スレッド情報をJSON形式で返す
 */

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/get_info.inc.php';

$_login->authorize(); // ユーザ認証

$host = isset($_GET['host']) ? $_GET['host'] : null; // "pc.2ch.net"
$bbs  = isset($_GET['bbs'])  ? $_GET['bbs']  : null; // "php"
$key  = isset($_GET['key'])  ? $_GET['key']  : null; // "1022999539"

header('Content-Type: application/json; charset=UTF-8');
if (!$host || !$bbs) {
    echo 'null';
} elseif (!$key) {
    echo info_js_get_board_info($host, $bbs);
} else {
    echo info_js_get_thread_info($host, $bbs, $key);
}

// {{{ info_js_get_board_info()

/**
 * 板情報を取得する
 *
 * @param   string  $host
 * @param   string  $bbs
 * @return  string  JSONエンコードされた板情報
 */
function info_js_get_board_info($host, $bbs)
{
    return info_js_json_encode(get_board_info($host, $bbs));
}

// }}}
// {{{ info_js_get_thread_info()

/**
 * スレッド情報を取得する
 *
 * @param   string  $host
 * @param   string  $bbs
 * @param   string  $key
 * @return  string  JSONエンコードされたスレッド情報
 */
function info_js_get_thread_info($host, $bbs, $key)
{
    return info_js_json_encode(get_thread_info($host, $bbs, $key));
}

// }}}
// {{{ info_js_json_encode()

/**
 * Shift_JISの値をUTF-8に変換してからJSONエンコードする
 *
 * @param   mixed   $values
 * @return  string  JSON
 */
function info_js_json_encode($values)
{
    mb_convert_variables('UTF-8', 'CP932', $values);
    return json_encode($values);
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
