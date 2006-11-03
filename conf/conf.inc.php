<?php
/*
    rep2 - 基本設定ファイル

    このファイルは、特に理由の無い限り変更しないこと
*/

$_conf['p2version'] = '1.8.8'; // rep2のバージョン

$_conf['p2name'] = 'r e p 2';    // rep2の名前。


//======================================================================
// 基本設定処理
//======================================================================
// エラー出力設定（今はNOTICEを出さないコードを心掛けているけれど、昔書いた部分でたくさん出ると思う）
error_reporting(E_ALL ^ E_NOTICE);

// {{{ 基本変数

$_conf['p2web_url']             = 'http://akid.s17.xrea.com/';
$_conf['p2ime_url']             = 'http://akid.s17.xrea.com/p2ime.php';
$_conf['favrank_url']           = 'http://akid.s17.xrea.com/favrank/favrank.php';
$_conf['menu_php']              = 'menu.php';
$_conf['subject_php']           = 'subject.php';
$_conf['read_php']              = 'read.php';
$_conf['read_new_php']          = 'read_new.php';
$_conf['read_new_k_php']        = 'read_new_k.php';
$_conf['cookie_file_name']      = 'p2_cookie.txt';

$_info_msg_ht = ''; // ユーザ通知用 情報メッセージHTML

// }}}
// {{{ デバッグ

$debug = isset($_GET['debug'])? $_GET['debug'] : 0;
if ($debug) {
    include_once 'Benchmark/Profiler.php';
    $profiler =& new Benchmark_Profiler(true);
    
    // printMemoryUsage();
    register_shutdown_function('printMemoryUsage');
}

// }}}
// {{{ 動作環境を確認

if (version_compare(phpversion(), '4.3.0', 'lt')) {
    die('<html><body><h3>p2 error: PHPバージョン4.3.0未満では使えません。</h3></body></html>');
}
if (ini_get('safe_mode')) {
    die('<html><body><h3>p2 error: セーフモードで動作するPHPでは使えません。</h3></body></html>');
}
if (!extension_loaded('mbstring')) {
    die('<html><body><h3>p2 error: PHPのインストールが不十分です。PHPのmbstring拡張モジュールがロードされていません。</h3></body></html>');
}

// }}}
// {{{ 環境設定

// タイムゾーンをセット
if (function_exists('date_default_timezone_set')) { 
    date_default_timezone_set('Asia/Tokyo'); 
} else { 
    @putenv('TZ=JST-9'); 
}

@set_time_limit(60); // (60) スクリプト実行制限時間(秒)

// 自動フラッシュをオフにする
ob_implicit_flush(0);

// クライアントから接続を切られても処理を続行する
// ignore_user_abort(1);

// session.trans_sid有効時 や output_add_rewrite_var(), http_build_query() 等で生成・変更される
// URLのGETパラメータ区切り文字(列)を"&amp;"にする。（デフォルトは"&"）
ini_set('arg_separator.output', '&amp;');

// リクエストIDを設定
define('P2_REQUEST_ID', substr($_SERVER['REQUEST_METHOD'], 0, 1) . md5(serialize($_REQUEST)));

// Windows なら
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ';');
    defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '\\');
} else {
    defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ':');
    defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '/');
}

// }}}
// {{{ 文字コードの指定

// mb_detect_order("SJIS-win,eucJP-win,ASCII");
mb_internal_encoding('SJIS-win');
mb_http_output('pass');
mb_substitute_character(63); // 文字コード変換に失敗した文字が "?" になる
//mb_substitute_character(0x3013); // 〓
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
    $include_path = '.';
    if (is_dir(P2_PEAR_HACK_DIR)) {
        $include_path .= PATH_SEPARATOR . realpath(P2_PEAR_HACK_DIR);
    }
    if (is_dir(P2_PEAR_DIR)) {
        $include_path .= PATH_SEPARATOR . realpath(P2_PEAR_DIR);
    }
    $include_path .= PATH_SEPARATOR . ini_get('include_path');
    ini_set('include_path', $include_path);
}

// ライブラリを読み込む
$pear_required = array(
    'File/Util.php'             => 'File',
    'Net/UserAgent/Mobile.php'  => 'Net_UserAgent_Mobile',
    'PHP/Compat.php'            => 'PHP/Compat.php',
    'HTTP/Request.php'          => 'HTTP_Request'
);
foreach ($pear_required as $pear_file => $pear_pkg) {
    if (!include_once($pear_file)) {
        $url = 'http://akid.s17.xrea.com/p2puki/pukiwiki.php?PEAR%A4%CE%A5%A4%A5%F3%A5%B9%A5%C8%A1%BC%A5%EB';
        $url_t = $_conf['p2ime_url'] . "?enc=1&amp;url=" . rawurlencode($url);
        $msg = '<html><body><h3>p2 error: PEAR の ' . $pear_pkg . ' がインストールされていません</h3>
            <p><a href="' . $url_t . '" target="_blank">p2Wiki: PEARのインストール</a></p>
            </body></html>';
        die($msg);
    }
}

require_once P2_LIBRARY_DIR . '/p2util.class.php';
require_once P2_LIBRARY_DIR . '/dataphp.class.php';
require_once P2_LIBRARY_DIR . '/session.class.php';
require_once P2_LIBRARY_DIR . '/login.class.php';

// }}}
// {{{ PEAR::PHP_CompatでPHP5互換の関数を読み込む

if (version_compare(phpversion(), '5.0.0', '<')) {
    PHP_Compat::loadFunction('file_put_contents');
    //PHP_Compat::loadFunction('clone');
    PHP_Compat::loadFunction('scandir');
    PHP_Compat::loadFunction('http_build_query');
    //PHP_Compat::loadFunction('array_walk_recursive');
}

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

// 管理者用設定を読み込み
if (!include_once './conf/conf_admin.inc.php') {
    P2Util::printSimpleHtml("p2 error: 管理者用設定ファイルを読み込めませんでした。");
    die('');
}

// 管理用保存ディレクトリ (パーミッションは707)
$_conf['admin_dir'] = $_conf['data_dir'] . '/admin';

// cache 保存ディレクトリ (パーミッションは707)
$_conf['cache_dir'] = $_conf['data_dir'] . '/cache'; // 2005/6/29 $_conf['pref_dir'] . '/p2_cache' より変更

// テンポラリディレクトリ (パーミッションは707)
$_conf['tmp_dir'] = $_conf['data_dir'] . '/tmp';

$_conf['doctype'] = '';
$_conf['accesskey'] = 'accesskey';

$_conf['meta_charset_ht'] = '<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">'."\n";

// {{{ 端末判定

$_conf['login_check_ip']  = 1; // ログイン時にIPアドレスを検証する

$mobile = &Net_UserAgent_Mobile::singleton();

// PC
if ($mobile->isNonMobile()) {
    $_conf['ktai'] = FALSE;
    $_conf['disable_cookie'] = FALSE;

    if (P2Util::isBrowserSafariGroup()) {
        $_conf['accept_charset'] = 'UTF-8';
    } else {
        $_conf['accept_charset'] = 'Shift_JIS';
    }

// 携帯
} else {
    require_once P2_LIBRARY_DIR . '/hostcheck.class.php';
    
    $_conf['ktai'] = TRUE;
    $_conf['accept_charset'] = 'Shift_JIS';

    // ベンダ判定
    // DoCoMo i-Mode
    if ($mobile->isDoCoMo()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrDocomo()) {
            P2Util::printSimpleHtml("p2 error: UAがDoCoMoですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die('');
        }
        $_conf['disable_cookie'] = TRUE;
        
    // EZweb (au or Tu-Ka)
    } elseif ($mobile->isEZweb()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAu()) {
            P2Util::printSimpleHtml("p2 error: UAがEZwebですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die('');
        }
        $_conf['disable_cookie'] = FALSE;
        
    // Vodafone Live!
    } elseif ($mobile->isVodafone()) {
        //$_conf['accesskey'] = 'DIRECTKEY';
        // W型端末と3GC型端末はCookieが使える
        if ($mobile->isTypeW() || $mobile->isType3GC()) {
            $_conf['disable_cookie'] = FALSE;
        } else {
            $_conf['disable_cookie'] = TRUE;
            if ($_conf['login_check_ip'] && !HostCheck::isAddrVodafone()) {
                P2Util::printSimpleHtml("p2 error: UAがVodafoneですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
                die('');
            }
        }

    // AirH" Phone
    } elseif ($mobile->isAirHPhone()) {
        /*
        // AirH"では端末ID認証を行わないので、コメントアウト
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAirh()) {
            P2Util::printSimpleHtml("p2 error: UAがAirH&quot;ですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die('');
        }
        */
        $_conf['disable_cookie'] = FALSE;
        
    // その他
    } else {
        $_conf['disable_cookie'] = TRUE;
    }
}

// }}}
// {{{ クエリーによる強制ビュー指定

// b=pc はまだリンク先が完全でない
// output_add_rewrite_var() は便利だが、出力がバッファされて体感速度が落ちるのが難点。。
// 体感速度を落とさない良い方法ないかな？

// 強制PCビュー指定
$b = null;
if (isset($_GET['b'])) {
    $b = $_GET['b'];
} elseif (isset($_POST['b'])) {
    $b = $_POST['b'];
}
$k = null;
if (isset($_GET['k'])) {
    $k = $_GET['k'];
} elseif (isset($_POST['k'])) {
    $k = $_POST['k'];
}

$_conf['k_at_q'] = '';
$_conf['k_input_ht'] = '';
if ($b == 'pc') {
    $_conf['b'] = 'pc';
    $_conf['ktai'] = false;
    //output_add_rewrite_var('b', 'pc');

    $_conf['k_at_a'] = '&amp;b=pc';
    $_conf['k_at_q'] = '?b=pc';
    $_conf['k_input_ht'] = '<input type="hidden" name="b" value="pc">';

// 強制携帯ビュー指定（b=k。k=1は過去互換用）
} elseif ($b == 'k' || $k) {
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

// {{{ ユーザ設定 読込

// デフォルト設定（conf_user_def.inc.php）を読み込む
include_once './conf/conf_user_def.inc.php';
$_conf = array_merge($_conf, $conf_user_def);

// ユーザ設定があれば読み込む
$_conf['conf_user_file'] = $_conf['pref_dir'] . '/conf_user.srd.cgi';

// 旧形式ファイルをコピー
$conf_user_file_old = $_conf['pref_dir'] . '/conf_user.inc.php';
if (!file_exists($_conf['conf_user_file']) && file_exists($conf_user_file_old)) {
    $old_cont = DataPhp::getDataPhpCont($conf_user_file_old);
    FileCtl::make_datafile($_conf['conf_user_file'], $_conf['conf_user_perm']);
    file_put_contents($_conf['conf_user_file'], $old_cont);
}

$conf_user = array();
if (file_exists($_conf['conf_user_file'])) {
    if ($cont = file_get_contents($_conf['conf_user_file'])) {
        $conf_user = unserialize($cont);
        $_conf = array_merge($_conf, $conf_user);
    }
}

// }}}

if (file_exists("./conf/conf_user_style.inc.php")) {
    include_once "./conf/conf_user_style.inc.php"; // デザイン設定 読込
}

// {{{ デフォルト設定

if (!is_dir($_conf['pref_dir']))    { $_conf['pref_dir'] = "./data"; }
if (!is_dir($_conf['dat_dir']))     { $_conf['dat_dir'] = "./data"; }
if (!is_dir($_conf['idx_dir']))     { $_conf['idx_dir'] = "./data"; }
if (!isset($_conf['rct_rec_num']))  { $_conf['rct_rec_num'] = 20; }
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

if ($_conf['mobile.match_color']) {
    $_conf['k_filter_marker'] = "<font color=\"" . htmlspecialchars($_conf['mobile.match_color']) . "\">\\1</font>";
} else {
    $_conf['k_filter_marker'] = null;
}

//======================================================================
// 変数設定
//======================================================================
$_conf['rct_file'] =            $_conf['pref_dir'] . '/p2_recent.idx';
$_conf['p2_res_hist_dat'] =     $_conf['pref_dir'] . '/p2_res_hist.dat'; // 書き込みログファイル（dat）
$_conf['p2_res_hist_dat_php'] = $_conf['pref_dir'] . '/p2_res_hist.dat.php'; // 書き込みログファイル（データPHP）
$_conf['cookie_dir'] =          $_conf['pref_dir'] . '/p2_cookie'; // cookie 保存ディレクトリ
$_conf['favlist_file'] =        $_conf['pref_dir'] . "/p2_favlist.idx";
$_conf['favita_path'] =         $_conf['pref_dir'] . "/p2_favita.brd";
$_conf['idpw2ch_php'] =         $_conf['pref_dir'] . "/p2_idpw2ch.php";
$_conf['sid2ch_php'] =          $_conf['pref_dir'] . "/p2_sid2ch.php";
$_conf['auth_user_file'] =      $_conf['pref_dir'] . "/p2_auth_user.php";
$_conf['auth_ez_file'] =        $_conf['pref_dir'] . "/p2_auth_ez.php";
$_conf['auth_jp_file'] =        $_conf['pref_dir'] . "/p2_auth_jp.php";
$_conf['auth_docomo_file'] =    $_conf['pref_dir'] . '/p2_auth_docomo.php';
$_conf['login_log_file'] =      $_conf['pref_dir'] . "/p2_login.log.php";
$_conf['login_failed_log_file'] = $_conf['pref_dir'] . '/p2_login_failed.dat.php';

// saveMatomeCache() のために $_conf['pref_dir'] を絶対パスに変換する
define('P2_PREF_DIR_REAL_PATH', File_Util::realPath($_conf['pref_dir']));

$_conf['matome_cache_path'] = P2_PREF_DIR_REAL_PATH . DIRECTORY_SEPARATOR . 'matome_cache';
$_conf['matome_cache_ext'] = '.htm';
$_conf['matome_cache_max'] = 3; // 予備キャッシュの数

// {{{ ありえない引数のエラー

// 新規ログインとメンバーログインの同時指定はありえないので、エラー出す
if (isset($_POST['submit_new']) && isset($_POST['submit_member'])) {
    P2Util::printSimpleHtml("p2 Error: 無効なURLです。");
    die('');
}

// }}}
// {{{ ホストチェック

if ($_conf['secure']['auth_host'] || $_conf['secure']['auth_bbq']) {
    require_once P2_LIBRARY_DIR . '/hostcheck.class.php';
    if (($_conf['secure']['auth_host'] && HostCheck::getHostAuth() == FALSE) ||
        ($_conf['secure']['auth_bbq'] && HostCheck::getHostBurned() == TRUE)
    ) {
        HostCheck::forbidden();
    }
}

// }}}
// {{{ セッション

// 名前は、セッションクッキーを破棄するときのために、セッション利用の有無に関わらず設定する
session_name('PS');

// セッションデータ保存ディレクトリを規定
if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
    // $_conf['data_dir'] を絶対パスに変換する
    define('P2_DATA_DIR_REAL_PATH', File_Util::realPath($_conf['data_dir']));
    $_conf['session_dir'] = P2_DATA_DIR_REAL_PATH . DIRECTORY_SEPARATOR . 'session';
}


// css.php は特別にセッションから外す。
//if (basename($_SERVER['SCRIPT_NAME']) != 'css.php') {
    if ($_conf['use_session'] == 1 or ($_conf['use_session'] == 2 && !$_COOKIE['cid'])) { 
    
        // {{{ セッションデータ保存ディレクトリを設定
        
        if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
        
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
        
        // }}}

        $_p2session =& new Session();
        if ($_conf['disable_cookie'] && !ini_get('session.use_trans_sid')) {
            output_add_rewrite_var(session_name(), session_id());
        }
    }
//}

// }}}

// ログインクラスのインスタンス生成（ログインユーザが指定されていなければ、この時点でログインフォーム表示に）
require_once P2_LIBRARY_DIR . '/login.class.php';
$_login =& new Login();


//=====================================================================
// 関数
//=====================================================================
/**
 * conf_user にデータをセット記録する
 * maru_kakiko
 *
 * @return  true|null|false
 */
function setConfUser($k, $v)
{
    global $_conf;
    
    // validate
    if ($k == 'k_use_aas') {
        if ($v != 0 && $v != 1) {
            return null;
        }
    }
    
    if (false === P2Util::updateArraySrdFile(array($k => $v), $_conf['conf_user_file'])) {
        return false;
    }
    $_conf[$k] = $v;
    
    return true;
}

/**
 * 再帰的にstripslashesをかける
 * GET/POST/COOKIE変数用なのでオブジェクトのプロパティには対応しない
 * (ExUtil)
 *
 * @return  array|string
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
 *
 * @return  array|string
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

/**
 * メモリの使用量を表示する
 *
 * @return void
 */
function printMemoryUsage()
{
    $kb = memory_get_usage() / 1024;
    $kb = number_format($kb, 2, '.', '');
    
    echo 'Memory Usage: ' . $kb . 'KB';
}

/**
 * すごく適当な分かち書き用正規表現
 */
$GLOBALS['WAKATI_REGEX'] = mb_convert_encoding(
    '/(' . implode('|', array(
        //'[一-龠]+[ぁ-ん]*',
        //'[一-龠]+',
        '[一二三四五六七八九十]+',
        '[丁-龠]+',
        '[ぁ-ん][ぁ-んー～゛゜]*',
        '[ァ-ヶ][ァ-ヶー～゛゜]*',
        //'[a-z][a-z_\\-]*',
        //'[0-9][0-9.]*',
        '[0-9a-z][0-9a-z_\\-]*',
    )) . ')/u', 'UTF-8', 'SJIS-win');

/**
 * すごく適当な正規化＆分かち書き関数
 *
 * @return  array
 */
function wakati($str)
{
    return array_filter(array_map('trim', preg_split($GLOBALS['WAKATI_REGEX'],
        mb_strtolower(mb_convert_kana(mb_convert_encoding(
            $str, 'UTF-8', 'SJIS-win'), 'KVas', 'UTF-8'), 'UTF-8'),
        -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)), 'strlen');
}

?>