<?php
/*
    p2 - 基本設定ファイル

    このファイルは、特に理由の無い限り変更しないこと
*/

$_conf['p2version'] = '1.6.5';

//$_conf['p2name'] = 'p2';  // p2の名前。
$_conf['p2name'] = 'P2';    // p2の名前。


//======================================================================
// 基本設定処理
//======================================================================
error_reporting(E_ALL ^ E_NOTICE); // エラー出力設定

$debug = 0;
isset($_GET['debug']) and $debug = $_GET['debug'];
if ($debug) {
    include_once 'Benchmark/Profiler.php';
    $profiler =& new Benchmark_Profiler(true);
    
    // printMemoryUsage();
    register_shutdown_function('printMemoryUsage');
}

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

@putenv('TZ=JST-9'); // タイムゾーンをセット

// 自動フラッシュをオフにする
ob_implicit_flush(0);

// クライアントから接続を切られても処理を続行する
// ignore_user_abort(1);

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

// ■内部処理における文字コード指定
// mb_detect_order("SJIS-win,eucJP-win,ASCII");
mb_internal_encoding('SJIS-win');
mb_http_output('pass');
mb_substitute_character(63); // 文字コード変換に失敗した文字が "?" になる
// ob_start('mb_output_handler');

if (function_exists('mb_ereg_replace')) {
    define('P2_MBREGEX_AVAILABLE', 1);
    @mb_regex_encoding('SJIS-win');
} else {
    define('P2_MBREGEX_AVAILABLE', 0);
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
    ini_set('include_path', $_include_path);
}

// ユーティリティクラスを読み込む
require_once (P2_LIBRARY_DIR . '/p2util.class.php');

// }}}
// {{{ PEAR::PHP_CompatでPHP5互換の関数を読み込む
/*
if (version_compare(phpversion(), '5.0.0', '<')) {
    require_once 'PHP/Compat.php';
    PHP_Compat::loadFunction('clone');
    PHP_Compat::loadFunction('scandir');
    PHP_Compat::loadFunction('http_build_query');
    PHP_Compat::loadFunction('array_walk_recursive');
}
*/
// }}}
// {{{ フォームからの入力を一括でサニタイズ

/**
 * フォームからの入力を一括でクォート除去＆文字コード変換
 * フォームのaccept-encoding属性をUTF-8(Safari系) or Shift_JIS(その他)にし、
 * さらにhidden要素で美乳テーブルの文字を仕込むことで誤判定を減らす
 * 変換元候補にeucJP-winがあるのはHTTP入力の文字コードがEUCに自動変換されるサーバのため
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (get_magic_quotes_gpc()) {
        $_POST = array_map('stripslashes_r', $_POST);
    }
    mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_POST);
    $_POST = array_map('nullfilter_r', $_POST);
} elseif (!empty($_GET)) {
    if (get_magic_quotes_gpc()) {
        $_GET = array_map('stripslashes_r', $_GET);
    }
    mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_GET);
    $_GET = array_map('nullfilter_r', $_GET);
}

// }}}

if (P2Util::isBrowserSafariGroup()) {
    $_conf['accept_charset'] = 'UTF-8';
} else {
    $_conf['accept_charset'] = 'Shift_JIS';
}


$_conf['doctype'] = '';
$_conf['accesskey'] = 'accesskey';

// {{{ 携帯アクセスキー
$_conf['k_accesskey']['matome'] = '3';  // 新まとめ
$_conf['k_accesskey']['latest'] = '3';  // 新
$_conf['k_accesskey']['res'] = '7';     // ﾚｽ
$_conf['k_accesskey']['above'] = '2';   // 上
$_conf['k_accesskey']['up'] = '5';      // （板）
$_conf['k_accesskey']['prev'] = '4';    // 前
$_conf['k_accesskey']['bottom'] = '8';  // 下
$_conf['k_accesskey']['next'] = '6';    // 次
$_conf['k_accesskey']['info'] = '9';    // 情
$_conf['k_accesskey']['dele'] = '*';    // 削
$_conf['k_accesskey']['filter'] = '#';  // 索

$_conf['meta_charset_ht'] = '<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">'."\n";

// {{{ 端末判定

require_once 'Net/UserAgent/Mobile.php';
$mobile = &Net_UserAgent_Mobile::singleton();

// PC
if ($mobile->isNonMobile()) {
    $_conf['ktai'] = FALSE;

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
    // Vodafone Live!
    if ($mobile->isVodafone()) {
        $_conf['accesskey'] = 'DIRECTKEY';
    }
}

// }}}
// {{{ クエリーによる強制ビュー指定

// b=pc はまだリンク先が完全でない
// output_add_rewrite_var() は便利だが、出力がバッファされて体感速度が落ちるのが難点。。
// 体感速度を落とさない良い方法ないかな？

// PC（b=pc）
if ($_GET['b'] == 'pc' || $_POST['b'] == 'pc') {
    $_conf['b'] = 'pc';
    $_conf['ktai'] = false;
    //output_add_rewrite_var('b', 'pc');

    $_conf['k_at_a'] = '&amp;b=pc';
    $_conf['k_at_q'] = '?b=pc';
    $_conf['k_input_ht'] = '<input type="hidden" name="b" value="pc">';

// 携帯（b=k。k=1は過去互換用）
} elseif (!empty($_GET['k']) || !empty($_POST['k']) || $_GET['b'] == 'k' || $_POST['b'] == 'k') {
    $_conf['b'] = 'k';
    $_conf['ktai'] = true;
    //output_add_rewrite_var('b', 'k');
    
    $_conf['k_at_a'] = '&amp;b=k';
    $_conf['k_at_q'] = '?b=k';
    $_conf['k_input_ht'] = '<input type="hidden" name="b" value="k">';
}
// }}}

$_conf['k_to_index_ht'] = <<<EOP
<a {$_conf['accesskey']}="0" href="index.php{$_conf['k_at_q']}">0.TOP</a>
EOP;

// {{{ DOCTYPE HTML 宣言
$ie_strict = false;
if (empty($_conf['ktai'])) {
    if ($ie_strict) {
        $_conf['doctype'] = <<<EODOC
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">\n
EODOC;
    } else {
        $_conf['doctype'] = <<<EODOC
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">\n
EODOC;
    }
}
// }}}

//======================================================================

if (file_exists("./conf/conf_user.inc.php")) {
    include_once "./conf/conf_user.inc.php"; // ユーザ設定 読込
}
if (file_exists("./conf/conf_user_style.inc.php")) {
    include_once "./conf/conf_user_style.inc.php"; // デザイン設定 読込
}

$_conf['display_threads_num'] = 150; // (150) スレッドサブジェクト一覧のデフォルト表示数
$posted_rec_num = 1000; // (1000) 書き込んだレスの最大記録数 //現在は機能していない

$_conf['p2status_dl_interval'] = 360; // (360) p2status（アップデートチェック）のキャッシュを更新せずに保持する時間 (分)

// {{{ デフォルト設定
if (!isset($login['use'])) { $login['use'] = 1; }
if (!is_dir($_conf['pref_dir'])) { $_conf['pref_dir'] = "./data"; }
if (!is_dir($_conf['dat_dir'])) { $_conf['dat_dir'] = "./data"; }
if (!isset($_conf['rct_rec_num'])) { $_conf['rct_rec_num'] = 20; }
if (!isset($_conf['res_hist_rec_num'])) { $_conf['res_hist_rec_num'] = 20; }
if (!isset($posted_rec_num)) { $posted_rec_num = 1000; }
if (!isset($_conf['before_respointer'])) { $_conf['before_respointer'] = 20; }
if (!isset($_conf['sort_zero_adjust'])) { $_conf['sort_zero_adjust'] = 0.1; }
if (!isset($_conf['display_threads_num'])) { $_conf['display_threads_num'] = 150; }
if (!isset($_conf['cmp_dayres_midoku'])) { $_conf['cmp_dayres_midoku'] = 1; }
if (!isset($_conf['k_sb_disp_range'])) { $_conf['k_sb_disp_range'] = 30; }
if (!isset($_conf['k_rnum_range'])) { $_conf['k_rnum_range'] = 10; }
if (!isset($_conf['pre_thumb_height'])) { $_conf['pre_thumb_height'] = "32"; }
if (!isset($_conf['quote_res_view'])) { $_conf['quote_res_view'] = 1; }
if (!isset($_conf['res_write_rec'])) { $_conf['res_write_rec'] = 1; }

if (!isset($STYLE['post_pop_size'])) { $STYLE['post_pop_size'] = "610,350"; }
if (!isset($STYLE['post_msg_rows'])) { $STYLE['post_msg_rows'] = 10; }
if (!isset($STYLE['post_msg_cols'])) { $STYLE['post_msg_cols'] = 70; }
if (!isset($STYLE['info_pop_size'])) { $STYLE['info_pop_size'] = "600,380"; }
// }}}

// {{{ ユーザ設定の調整処理
$_conf['ext_win_target'] && $_conf['ext_win_target_at'] = " target=\"{$_conf['ext_win_target']}\"";
$_conf['bbs_win_target'] && $_conf['bbs_win_target_at'] = " target=\"{$_conf['bbs_win_target']}\"";

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

//======================================================================
// 変数設定
//======================================================================
$_conf['login_log_rec'] = 1; // ログインログの記録可否
$_conf['login_log_rec_num'] = 100; // ログインログの記録数
$_conf['last_login_log_show'] = 1; // 前回ログイン情報表示可否

$_conf['p2web_url'] = "http://akid.s17.xrea.com/";
$_conf['p2ime_url'] = "http://akid.s17.xrea.com/p2ime.php";
$_conf['favrank_url'] = "http://akid.s17.xrea.com:8080/favrank/favrank.php";
$_conf['menu_php'] = "menu.php";
$_conf['subject_php'] = "subject.php";
$_conf['read_php'] = "read.php";
$_conf['read_new_php'] = "read_new.php";
$_conf['read_new_k_php'] = "read_new_k.php";
$_conf['rct_file'] = $_conf['pref_dir'] . '/' . 'p2_recent.idx';
$_conf['p2_res_hist_dat'] = $_conf['pref_dir'] . '/p2_res_hist.dat'; // 書き込みログファイル（dat）
$_conf['p2_res_hist_dat_php'] = $_conf['pref_dir'] . '/p2_res_hist.dat.php'; // 書き込みログファイル（データPHP）
$_conf['cache_dir'] = $_conf['pref_dir'] . '/p2_cache';
$_conf['cookie_dir'] = $_conf['pref_dir'] . '/p2_cookie'; // cookie 保存ディレクトリ
$_conf['cookie_file_name'] = 'p2_cookie.txt';
$_conf['favlist_file'] = $_conf['pref_dir'] . "/" . "p2_favlist.idx";
$_conf['favita_path'] = $_conf['pref_dir'] . "/" . "p2_favita.brd";
$_conf['idpw2ch_php'] = $_conf['pref_dir'] . "/p2_idpw2ch.php";
$_conf['sid2ch_php'] = $_conf['pref_dir'] . "/p2_sid2ch.php";
$_conf['auth_user_file'] = $_conf['pref_dir'] . "/p2_auth_user.php";
$_conf['auth_ez_file'] = $_conf['pref_dir'] . "/p2_auth_ez.php";
$_conf['auth_jp_file'] = $_conf['pref_dir'] . "/p2_auth_jp.php";
$_conf['login_log_file'] = $_conf['pref_dir'] . "/p2_login.log.php";

$_conf['idx_dir'] = $_conf['dat_dir'];

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

$_conf['matome_cache_ext'] = '.htm';
$_conf['matome_cache_max'] = 3; // 予備キャッシュの数

$_conf['md5_crypt_key'] = $_SERVER['SERVER_NAME'].$_SERVER['SERVER_SOFTWARE'];
$_conf['menu_dl_interval'] = 1; // (1) 板 menu のキャッシュを更新せずに保持する時間 (hour)
$_conf['fsockopen_time_limit'] = 10; // (10) ネットワーク接続タイムアウト時間(秒)
set_time_limit(60); // スクリプト実行制限時間(秒)

// {{{ パーミッション設定
$_conf['data_dir_perm'] = 0707; // データ保存用ディレクトリ
$_conf['dat_perm'] = 0606; // datファイル
$_conf['key_perm'] = 0606; // key.idx ファイル
$_conf['dl_perm'] = 0606; // その他のp2が内部的にDL保存するファイル（キャッシュ等）
$_conf['pass_perm'] = 0604; // パスワードファイル
$_conf['p2_perm'] = 0606; // その他のp2の内部保存データファイル
$_conf['palace_perm'] = 0606; // 殿堂入り記録ファイル
$_conf['favita_perm'] = 0606; // お気に板記録ファイル
$_conf['favlist_perm'] = 0606; // お気にスレ記録ファイル
$_conf['rct_perm'] = 0606; // 最近読んだスレ記録ファイル
$_conf['res_write_perm'] = 0606; // 書き込み履歴記録ファイル
// }}}

//=====================================================================
// 関数
//=====================================================================

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
 * 再帰的にstripslashesをかける
 * GET/POST/COOKIE変数用なのでオブジェクトのプロパティには対応しない
 * (ExUtil)
 */
function stripslashes_r($var, $r = 0)
{
    if (is_array($var) && $r < 3) {
        foreach ($var as $key => $value) {
            $var[$key] = stripslashes_r($value, ++$r);
        }
    } elseif (is_string($var)) {
        $var = stripslashes($var);
    }
    return $var;
}

/**
 * 再帰的にヌル文字を削除する
 * mbstringで変換テーブルにない(?)外字を変換すると
 * NULL(0x00)になってしまうことがあるので消去する
 * (ExUtil)
 */
function nullfilter_r($var, $r = 0)
{
    if (is_array($var) && $r < 3) {
        foreach ($var as $key => $value) {
            $var[$key] = nullfilter_r($value, ++$r);
        }
    } elseif (is_string($var)) {
        $var = str_replace(chr(0), '', $var);
    }
    return $var;
}

function printMemoryUsage()
{
    echo memory_get_usage();
}
?>
