<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - 基本設定ファイル

    このファイルは、特に理由の無い限り変更しないこと
*/

$_conf['p2version'] = "1.5.x";
$_conf['p2expack'] = "050610.0100";

$_conf['p2name'] = "WaterWeasel";   // p2の名前
//$_conf['p2name'] = "p2";
//$_conf['p2name'] = "P2";
//$_conf['p2name'] = "p++";
$_conf['p2name_ua'] = $_conf['p2name'];
//$_conf['p2version_ua'] = $_conf['p2version'];
$_conf['p2version_ua'] = $_conf['p2expack'];

//======================================================================
// 基本設定処理
//======================================================================

$_info_msg_ht = '';

// {{{ 動作環境を確認

if (version_compare(phpversion(), '4.3.0', 'lt')) {
    die('<html><body><h1>p2 info: PHPバージョン4.3.0未満では使えません。</h1></body></html>');
}
if (ini_get('safe_mode')) {
    die('<html><body><h1>p2 info: セーフモードで動作するPHPでは使えません。</h1></body></html>');
}
if (!extension_loaded('mbstring')) {
    die('<html><body><h1>p2 info: mbstring拡張モジュールがロードされていません。</h1></body></html>');
}

// }}}
// {{{ 環境設定

// エラー出力を設定
error_reporting(E_ALL ^ E_NOTICE);

// タイムゾーンをセット
putenv('TZ=JST-9');

// スクリプト実行制限時間(秒)
set_time_limit(60);

// 自動フラッシュをオフにする
ob_implicit_flush(0);

// クライアントから接続を切られても処理を続行する
ignore_user_abort(1);

// session.trans_sid有効時 や output_add_rewrite_var(), http_build_query() 等で生成・変更される
// URLのGETパラメータ区切り文字(列)を"&amp;"にする。（デフォルトは"&"）
ini_set('arg_separator.output', '&amp;');

// リクエストIDを設定
define('P2_REQUEST_ID', substr($_SERVER['REQUEST_METHOD'], 0, 1) . md5(serialize($_REQUEST)));

// OS判定
if (strstr(PHP_OS, 'WIN')) {
    // Windows
    defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ';');
    defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '\\');
} else {
    defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ':');
    defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '/');
}

// 内部処理における文字コード指定
mb_internal_encoding('SJIS-win');
mb_http_output('pass');
mb_substitute_character(63); // 文字コード変換に失敗した文字が "?" になる

if (function_exists('mb_ereg_replace')) {
    define('P2_MBREGEX_AVAILABLE', 1);
    mb_regex_encoding('SJIS-win');
} else {
    define('P2_MBREGEX_AVAILABLE', 0);
}

// DB_DataObjectでPHP 4.3.10のバグ回避
if (phpversion() == '4.3.10' && !defined('DB_DATAOBJECT_NO_OVERLOAD')) {
    define('DB_DATAOBJECT_NO_OVERLOAD', TRUE);
}

// }}}
// {{{ ライブラリ類のパス設定

// 基本的な機能を提供するするライブラリ
define('P2_LIBRARY_DIR', './lib');

// おまけ的な機能を提供するするライブラリ
define('P2EX_LIBRARY_DIR', './lib/expack');

// スタイルシート
define('P2_STYLE_DIR', './style');

// PEARインストールディレクトリ、検索パスに追加される
define('P2_PEAR_DIR', './includes');

// PEARをハックしたファイル用ディレクトリ、通常のPEARより優先的に検索パスに追加される
// Cache/Container/db.php(PEAR::Cache)がMySQL縛りだったので、汎用的にしたものを置いている
define('P2_PEAR_HACK_DIR', './lib/pear_hack');

// 検索パスをセット
if (is_dir(P2_PEAR_DIR) || is_dir(P2_PEAR_HACK_DIR)) {
    $_include_path = '.';
    if (is_dir(P2_PEAR_HACK_DIR)) {
        $_include_path .= PATH_SEPARATOR . realpath(P2_PEAR_HACK_DIR);
    }
    if (is_dir(P2_PEAR_DIR)) {
        $_include_path .= PATH_SEPARATOR . realpath(P2_PEAR_DIR);
    }
    $_include_path .= PATH_SEPARATOR . ini_get('include_path');
    set_include_path($_include_path);
}

// ユーティリティクラスを読み込む
require_once (P2_LIBRARY_DIR . '/p2util.class.php');

// }}}
// {{{ PEAR::PHP_CompatでPHP5互換の関数を読み込む

if (version_compare(phpversion(), '5.0.0', '<')) {
    require_once 'PHP/Compat.php';
    PHP_Compat::loadFunction('clone');
    PHP_Compat::loadFunction('scandir');
    PHP_Compat::loadFunction('http_build_query');
    PHP_Compat::loadFunction('array_walk_recursive');
}

// }}}
// {{{ フォームからの入力を一括でサニタイズ

// 文字化け防止のためフォームのaccept-encoding属性をUTF-8(Safari系) or Shift_JIS(その他)にし、
// 誤判定防止のためhidden要素の先頭に美乳テーブルの文字を仕込む。
// 変換元候補にeucJP-winがあるのはHTTP入力の文字コードがEUCに自動変換されるサーバのため。
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_REQUEST = &$_POST;
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $_REQUEST = &$_GET;
}
if (!empty($_REQUEST)) {
    if (get_magic_quotes_gpc()) {
        array_walk_recursive($_REQUEST, 'stripslashes_cb');
    }
    mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_REQUEST);
    array_walk_recursive($_REQUEST, 'nullfilter_cb');
}

// }}}
// {{{ 端末判定

require_once 'Net/UserAgent/Mobile.php';
$mobile = &Net_UserAgent_Mobile::singleton();

// PC
if ($mobile->isNonMobile()) {
    $_conf['ktai'] = FALSE;
    $_conf['enable_cookie'] = TRUE;

    if (P2Util::isBrowserSafariGroup()) {
        $_conf['accept_charset'] = 'UTF-8';
    } else {
        $_conf['accept_charset'] = 'Shift_JIS';
    }

// 携帯
} else {
    $_conf['ktai'] = TRUE;
    $_conf['accept_charset'] = 'Shift_JIS';

    // ベンダ判定
    // DoCoMo i-Mode
    if ($mobile->isDoCoMo()) {
        $_conf['enable_cookie'] = FALSE;
    // EZweb (au or Tu-Ka)
    } elseif ($mobile->isEZweb()) {
        $_conf['enable_cookie'] = TRUE;
    // Vodafone Live!
    } elseif ($mobile->isVodafone()) {
        $_conf['accesskey'] = 'DIRECTKEY';
        // W型端末と3GC型端末はCookieが使える
        if ($mobile->isTypeW() || $mobile->isType3GC()) {
            $_conf['enable_cookie'] = TRUE;
        } else {
            $_conf['enable_cookie'] = FALSE;
        }
    // AirH" Phone
    } elseif ($mobile->isAirHPhone()) {
        $_conf['enable_cookie'] = TRUE;
    // その他
    } else {
        $_conf['enable_cookie'] = FALSE;
    }
}

// }}}
// {{{ クエリーによる強制ビュー指定

// b=pc はまだリンク先が完全でない
// output_add_rewrite_var() は便利だが、出力がバッファされて体感速度が落ちるのが難点。。
// 体感速度を落とさない良い方法ないかな？

$_conf['b'] = NULL;
$_conf['b_force_view'] = FALSE;

if (isset($_GET['b'])) {
    $_conf['b'] = $_GET['b'];
} elseif (isset($_POST['b'])) {
    $_conf['b'] = $_POST['b'];
} elseif (!empty($_GET['k']) || !empty($_POST['k'])) {
    $_conf['b'] = 'k';
}

// PC（携帯でb=pc）
if ($_conf['ktai'] && $_conf['b'] == 'pc') {
    $_conf['ktai'] = FALSE;
    $_conf['b_force_view'] = TRUE;

// 携帯（PCでb=k。k=1は過去互換用）
} elseif (!$_conf['ktai'] && $_conf['b'] == 'k') {
    $_conf['ktai'] = TRUE;
    $_conf['b_force_view'] = TRUE;
}

// }}}
// {{{ ビュー変数設定

// 携帯
if ($_conf['ktai']) {
    $_conf['accesskey'] = 'accesskey';
    $_conf['k_accesskey']['matome'] = '3';  // 新まとめ // 3
    $_conf['k_accesskey']['latest'] = '3';  // 新 // 9
    $_conf['k_accesskey']['res'] = '7';     // ﾚｽ
    $_conf['k_accesskey']['above'] = '2';   // 上 // 2
    $_conf['k_accesskey']['up'] = '5';      // （板） // 5
    $_conf['k_accesskey']['prev'] = '4';    // 前 // 4
    $_conf['k_accesskey']['bottom'] = '8';  // 下 // 8
    $_conf['k_accesskey']['next'] = '6';    // 次 // 6
    $_conf['k_accesskey']['info'] = '9';    // 情
    $_conf['k_accesskey']['dele'] = '*';    // 削
    $_conf['k_accesskey']['filter'] = '#';  // 策

    $_conf['k_to_index_ht'] = "<a {$_conf['accesskey']}=\"0\" href=\"index.php\">0.TOP</a>";

    $_conf['meta_charset_ht'] = '';
    $_conf['doctype'] = '';
/*
    if ($mobile->isWAP2()) {
        $_conf['doctype'] = <<<EOP
<?xml version="1.0" encoding="Shift_JIS"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN"
 "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">\n
EOP;
    }
*/

// PC
} else {
    $_conf['meta_charset_ht'] = '<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">';
    // DOCTYPE HTML 宣言
    $ie_strict = false;
    if ($ie_strict) {
        $_conf['doctype'] = <<<EOP
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/html4/loose.dtd">\n
EOP;
    } else {
        $_conf['doctype'] = <<<EOP
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">\n
EOP;
    }
}

// }}}
// {{{ ユーザ設定

include_once 'conf/conf_user.php'; // ユーザ設定 読込
include_once 'conf/conf_user_ex.php'; // 拡張パックユーザ設定 読込

$_conf['display_threads_num'] = 150; // (150) スレッドサブジェクト一覧のデフォルト表示数
$_conf['posted_rec_num'] = 1000; // (1000) 書き込んだレスの最大記録数 //現在は機能していない

$_conf['p2status_dl_interval'] = 360;   // (360) p2status（アップデートチェック）のキャッシュを更新せずに保持する時間 (分)

/* デフォルト設定 */
if (!isset($login['use'])) { $login['use'] = 1; }
if (!is_dir($_conf['pref_dir'])) { $_conf['pref_dir'] = "./data"; }
if (!is_dir($datdir)) { $datdir = "./data"; }
if (!isset($_conf['rct_rec_num'])) { $_conf['rct_rec_num'] = 20; }
if (!isset($_conf['res_hist_rec_num'])) { $_conf['res_hist_rec_num'] = 20; }
if (!isset($_conf['posted_rec_num'])) { $_conf['posted_rec_num'] = 1000; }
if (!isset($_conf['before_respointer'])) { $_conf['before_respointer'] = 20; }
if (!isset($_conf['sort_zero_adjust'])) { $_conf['sort_zero_adjust'] = 0.1; }
if (!isset($_conf['display_threads_num'])) { $_conf['display_threads_num'] = 150; }
if (!isset($_conf['cmp_dayres_midoku'])) { $_conf['cmp_dayres_midoku'] = 1; }
if (!isset($_conf['k_sb_disp_range'])) { $_conf['k_sb_disp_range'] = 30; }
if (!isset($_conf['k_rnum_range'])) { $_conf['k_rnum_range'] = 10; }
if (!isset($_conf['pre_thumb_height'])) { $_conf['pre_thumb_height'] = "32"; }
if (!isset($_conf['quote_res_view'])) { $_conf['quote_res_view'] = 1; }
if (!isset($_conf['res_write_rec'])) { $_conf['res_write_rec'] = 1; }
if (!isset($_conf['frame_type'])) { $_conf['frame_type'] = 0; }
if (!isset($_conf['frame_cols'])) { $_conf['frame_cols'] = "156,*"; }
if (!isset($_conf['frame_rows'])) { $_conf['frame_rows'] = "40%,60%"; }

if (!isset($STYLE['post_pop_size'])) { $STYLE['post_pop_size'] = "610,350"; }
if (!isset($STYLE['post_msg_rows'])) { $STYLE['post_msg_rows'] = 10; }
if (!isset($STYLE['post_msg_cols'])) { $STYLE['post_msg_cols'] = 70; }
if (!isset($STYLE['info_pop_size'])) { $STYLE['info_pop_size'] = "600,380"; }

/* ユーザ設定の調整処理 */
$_conf['ext_win_target_at'] = '';
$_conf['bbs_win_target_at'] = '';
$_conf['accept_charset_at'] = '';
if (!$_conf['ktai']) {
    $_conf['ext_win_target'] && $_conf['ext_win_target_at'] = " target=\"{$_conf['ext_win_target']}\"";
    $_conf['bbs_win_target'] && $_conf['bbs_win_target_at'] = " target=\"{$_conf['bbs_win_target']}\"";
    $_conf['accept_charset'] && $_conf['accept_charset_at'] = " accept-charset=\"{$_conf['accept_charset']}\"";
}

if ($_conf['get_new_res']) {
    if ($_conf['get_new_res'] == 'all') {
        $_conf['get_new_res_l'] = $_conf['get_new_res'];
    } else {
        $_conf['get_new_res_l'] = 'l'.$_conf['get_new_res'];
    }
} else {
    $_conf['get_new_res_l'] = 'l200';
}

// }}}
// {{{ 変数設定

$_conf['login_log_rec']       = 1;  // ログインログの記録可否
$_conf['login_log_rec_num']   = 100;    // ログインログの記録数
$_conf['last_login_log_show'] = 1;  // 前回ログイン情報表示可否

$_conf['p2web_url']         = 'http://akid.s17.xrea.com/';
$_conf['p2ime_url']         = 'http://akid.s17.xrea.com/p2ime.php';
$_conf['favrank_url']       = 'http://akid.s17.xrea.com:8080/favrank/favrank.php';
$_conf['expack_url']        = 'http://moonshine.s32.xrea.com/';
$_conf['tgrep_url']         = 'http://moonshine.s32.xrea.com/test/tgrep.cgi';
$_conf['menu_php']          = 'menu.php';
$_conf['subject_php']       = 'subject.php';
$_conf['subject_rss_php']   = 'subject_rss.php';
$_conf['read_php']          = 'read.php';
$_conf['read_new_php']      = 'read_new.php';
$_conf['read_new_k_php']    = 'read_new_k.php';
$_conf['rct_file']          = $_conf['pref_dir'] . '/p2_recent.idx';
$_conf['cache_dir']         = $_conf['pref_dir'] . '/p2_cache';
$_conf['cookie_dir']        = $_conf['pref_dir'] . '/p2_cookie';    // cookie 保存ディレクトリ
$_conf['cookie_file_name']  = 'p2_cookie.txt';
$_conf['favlist_file']      = $_conf['pref_dir'] . '/p2_favlist.idx';
$_conf['favita_path']       = $_conf['pref_dir'] . '/p2_favita.brd';
$_conf['idpw2ch_php']       = $_conf['pref_dir'] . '/p2_idpw2ch.php';
$_conf['sid2ch_php']        = $_conf['pref_dir'] . '/p2_sid2ch.php';
$_conf['auth_user_file']    = $_conf['pref_dir'] . '/p2_auth_user.php';
$_conf['auth_ez_file']      = $_conf['pref_dir'] . '/p2_auth_ez.php';
$_conf['auth_jp_file']      = $_conf['pref_dir'] . '/p2_auth_jp.php';
$_conf['login_log_file']    = $_conf['pref_dir'] . '/p2_login.log.php';

// saveMatomeCache() のために $_conf['pref_dir'] を絶対パスに変換する
// ※環境によっては、realpath() で値を取得できない場合がある？
if ($rp = realpath($_conf['pref_dir'])) {
    $_conf['matome_cache_path'] = $rp.'/matome_cache';
} else {
    if (substr($_conf['pref_dir'], 0, 1) == '/') {
        $_conf['matome_cache_path'] = $_conf['pref_dir'] . '/matome_cache';
    } else {
        $GLOBALS['pref_dir_realpath_failed_msg'] = 'p2 error: realpath()の取得ができませんでした。ファイル conf.inc.php の $_conf[\'pref_dir\'] をルートからの絶対パス指定で設定してください。';
    }
}

$_conf['matome_cache_ext']  = '.htm';
$_conf['matome_cache_max']  = 3;    // 予備キャッシュの数

$_conf['md5_crypt_key']        = $_SERVER['SERVER_NAME'].$_SERVER['SERVER_SOFTWARE'];
$_conf['menu_dl_interval']     = 1; // menuのキャッシュを更新せずに保持する時間(hour)
$_conf['fsockopen_time_limit'] = 10;    // (10) ネットワーク接続タイムアウト時間(秒)

$_conf['data_dir_perm']  = 0707;
$_conf['dat_perm']       = 0606;
$_conf['key_perm']       = 0606;
$_conf['pass_perm']      = 0604;
$_conf['p2_perm']        = 0606;    // 見られてもあまり意味のない内部処理データファイル
$_conf['palace_perm']    = 0606;
$_conf['favita_perm']    = 0606;
$_conf['favlist_perm']   = 0606;
$_conf['rct_perm']       = 0606;
$_conf['res_write_perm'] = 0606;

// }}}
// {{{ 拡張パック 変数設定

$_conf['favset_num'] = 5;
$_conf['favset_file'] = $_conf['pref_dir'] . '/p2_favset.txt';

if ($enable_expack) {
    $_conf['skin_file'] = $_conf['pref_dir'].'/p2_user_skin.txt';
    $_conf['skin_perm'] = 0606;
    $_conf['rss_file']  = $_conf['pref_dir'].'/p2_rss.txt';
    $_conf['rss_perm']  = 0606;
    // アクティブモナーのAA判定にmb_ereg()を使うため
    if (!function_exists('mb_ereg')) {
        $_exconf['aMona']['*'] = 0;
    }
    // MacではSafari系（というかNSTextView?）以外は全角プロポーショナルフォント非対応なので
    if (strstr($_SERVER['HTTP_USER_AGENT'], 'Mac') && !(P2Util::isBrowserSafariGroup())) {
        $_exconf['aMona']['*'] = 0;
        $_exconf['spm']['with_aMona'] = 0;
        $_exconf['editor']['with_aMona'] = 0;
    }
} else {
    $_exconf['kanban']['*'] = 0;
    $_exconf['skin']['*'] = 0;
    $_exconf['aMona']['*'] = 0;
    $_exconf['fitImage']['*'] = 0;
    $_exconf['editor']['*'] = 0;
    $_exconf['bookmark']['*'] = 0;
    $_exconf['spm']['*'] = 0;
    $_exconf['rss']['*'] = 0;
    $_exconf['flex']['*'] = 0;
    $_exconf['imgCache']['*'] = 0;
    $_exconf['liveView']['*'] = 0;
    $_exconf['soap']['*'] = 0;
}

// }}}
// {{{ ホストチェック

if ($_exconf['secure']['auth_host'] || $_exconf['secure']['auth_bbq']) {
    require_once (P2EX_LIBRARY_DIR . '/hostcheck.class.php');
    if (($_exconf['secure']['auth_host'] && HostCheck::getHostAuth() == FALSE) ||
        ($_exconf['secure']['auth_bbq'] && HostCheck::getHostBurned() == TRUE)
    ) {
        HostCheck::forbidden();
    }
}

// }}}
// {{{ デザイン設定 読み込み

if (isset($_GET['skin']) && preg_match('/^\w+$/', $_GET['skin'])) {
    $skin_name = $_GET['skin'];
    $skin = 'skin/' . $skin_name . '.php';
} elseif (isset($_conf['skin_file'])) {
    if (file_exists($_conf['skin_file'])) {
        $skin_name = rtrim(array_shift(file($_conf['skin_file'])));
        $skin = 'skin/' . $skin_name . '.php';
    } else {
        require_once (P2_LIBRARY_DIR . '/filectl.class.php');
        FileCtl::make_datafile($_conf['skin_file'], $_conf['skin_perm']);
    }
}

if (!isset($skin) || !file_exists($skin)) {
    $skin_name = 'conf_user_style';
    $skin = 'conf/conf_user_style.php';
}

$skin_en = rawurlencode($skin_name);

@include_once ($skin);

if (is_array($STYLE)) {
    foreach ($STYLE as $sKey => $sValue) {
        if (strstr($sKey, 'background') && $sValue != "") {
            $STYLE[$sKey] = 'url("' . str_replace("'", "\\'", $sValue) . '")';
        }
        if (strstr($sKey, 'fontfamily') && is_array($sValue)) {
            $STYLE[$sKey] = implode('","', $sValue);
            $STYLE[$sKey] = preg_replace('/"(serif|sans-serif|cursive|fantasy|monospace)"/', "$1", $STYLE[$sKey]);
        }
        if (is_string($sValue) && preg_match('/^#([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/', $sValue, $sMatch)) {
            $STYLE[$sKey] = '#'.$sMatch[1].$sMatch[1].$sMatch[2].$sMatch[2].$sMatch[3].$sMatch[3];
        }
    }
}

if (isset($k_ngword_color)) {
    $STYLE['read_ngword'] = $k_ngword_color;
}

// }}}
// {{{ カラーリング設定（ユビキタス）

$k_color_settings = '';
if ($_conf['ktai']) {
    if ($_exconf['ubiq']['c_bgcolor']) {
        $k_color_settings .= " bgcolor=\"{$_exconf['ubiq']['c_bgcolor']}\"";
    }
    if ($_exconf['ubiq']['c_text']) {
        $k_color_settings .= " text=\"{$_exconf['ubiq']['c_text']}\"";
    }
    if ($_exconf['ubiq']['c_link']) {
        $k_color_settings .= " link=\"{$_exconf['ubiq']['c_link']}\"";
    }
    if ($_exconf['ubiq']['c_vlink']) {
        $k_color_settings .= " vlink=\"{$_exconf['ubiq']['c_vlink']}\"";
    }
    if ($_exconf['ubiq']['c_ngword']) {
        $k_ngword_color = $_exconf['ubiq']['c_ngword'];
    }
    // 携帯用マーカー
    if ($_exconf['ubiq']['c_match'] || $_exconf['ubiq']['b_match']) {
        $k_filter_marker = '\\1';
        if ($_exconf['ubiq']['c_match']) {
            $k_filter_marker = "<font color=\"{$_exconf['ubiq']['c_match']}\">" . $k_filter_marker . "</font>";
        }
        if ($_exconf['ubiq']['b_match']) {
            $k_filter_marker = '<b>' . $k_filter_marker . '</b>';
        }
    } else {
        $k_filter_marker = FALSE;
    }
}

// }}}
// {{{ 出力バッファリングを伴う機能

// KeepAlive接続を使うためにContent-Lengthヘッダを出力する。
// 有効にすると処理できた分から表示することができなくなり、体感上の速度は遅くなる。
// 他のバッファリングを伴う関数がコールされる前に実行しないと正しいContant-Lengthを取得できないので注意。
$_keep_alive = FALSE;
if ($_keep_alive) {
    ob_start(array('P2Util', 'header_content_length'));
}

// パケット節約
if ($_conf['ktai'] && $_exconf['ubiq']['save_packet']) {
    require_once (P2EX_LIBRARY_DIR . '/packetsaver.inc.php');
    ob_start('packet_saver');
    if (extension_loaded('tidy')) {
        define('P2_TIDY_REPAIR_OUTPUT', 1);
    }
}

// クエリによる強制ビュー設定のとき
if ($_conf['b'] && $_conf['b_force_view']) {
    output_add_rewrite_var('b', $_conf['b']);
}

// }}}
// {{{ セッション

// ※重要※
// php.ini で session.auto_start = 0 (PHPのデフォルトのまま) になっていること。
// さもないとほとんどのセッション関連のパラメータがスクリプト内で変更できない。
// .htaccessで変更が許可されているなら
/*
<IfModule mod_php4.c>
    php_flag session.auto_start Off
</IfModule>
*/
// でもOK。

// お気に入りセットの切り替え機能が有効ならセッション開始
if ($_exconf['etc']['multi_favs']) {
    require_once (P2_LIBRARY_DIR . '/favsetmng.class.php');

    // eAcceleratorのセッションハンドラを使ってみる
    /*if (extension_loaded('eAccelerator')) {
        eaccelerator_set_session_handlers();
    }*/

    // セッションデータ保存ディレクトリを設定
    if (session_module_name() == 'files') {
        $_conf['session_dir'] = $_conf['pref_dir'] . '/p2_session';

        if (!is_dir($_conf['session_dir'])) {
            require_once (P2_LIBRARY_DIR . '/filectl.class.php');
            FileCtl::mkdir_for($_conf['session_dir'] . '/dummy_filename');
        } elseif (!is_writable($_conf['session_dir'])) {
            die("Error: セッションデータ保存ディレクトリ ({$_conf['session_dir']}) に書き込み権限がありません。");
        }

        session_save_path($_conf['session_dir']);

        // session.save_path のパスの深さが2より大きいとガーベッジコレクションが行われないので
        // 自前でガーベッジコレクションする
        P2Util::session_gc();
    }

    // クッキーが使用可能な端末ではセッションIDをクッキー渡しに限定する
    if ($_conf['enable_cookie']) {
        ini_set('session.use_only_cookies', '1');
        // セッションクッキーパラメータを設定
        $_scp = session_get_cookie_params();
        if (dirname($_SERVER['PHP_SELF']) != '/') {
            $_scp[1] = dirname($_SERVER['PHP_SELF']) . '/';
            session_set_cookie_params($_scp[0], $_scp[1], $_scp[2], $_scp[3]);
        }
        unset($_scp);
    } else {
        ini_set('session.use_only_cookies', '0');
    }

    // セッション開始
    session_start();
    // お気にセットを切り替える
    FavSetManager::switchFavSet();
    // セッション変数の変更が必要なくなったらすぐセッションを終了する
    session_write_close();

    // session.use_trans_sid の変更の可否は PHP_INI_SYSTEM|PHP_INI_PERDIR なので
    // php.ini か .htaccess でしか変更できない。
    // 端末がCookie非対応で session.use_trans_sid = 0 (PHPのデフォルト) のサーバは
    // output_add_rewrite_var() を使ってセッションIDをクエリに埋め込む。
    if (!$_conf['enable_cookie'] && !ini_get('session.use_trans_sid')) {
        output_add_rewrite_var(session_name(), session_id());
//  } elseif (defined('SID') && SID != '') {
//      list($session_name, $session_id) = explode('=', SID);
//      output_add_rewrite_var($session_name, $session_id);
    }
}

// }}}
//======================================================================
// {{{ 関数

/**
 * 認証関数
 */
function authorize()
{
    global $login;

    if ($login['use']) {

        include_once (P2_LIBRARY_DIR . '/login.inc.php');

        // 認証チェック
        if (!authCheck()) {
            // ログイン失敗
            include_once (P2_LIBRARY_DIR . '/login_first.inc.php');
            printLoginFirst();
            exit;
        }

        // 要求があれば、補助認証を登録
        registCookie();
        registKtaiId();
     }

    return true;
}

/**
 * array_walk(_recursive)用のコールバック関数
 */
function addslashes_cb(&$value, $key)
{
    $value = addslashes($value);
}
function stripslashes_cb(&$value, $key)
{
    $value = stripslashes($value);
}
function nullfilter_cb(&$value, $key)
{
    $value = str_replace(chr(0), '', $value);
}

// }}}
?>
