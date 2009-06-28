<?php
/*
    rep2 - 基本設定ファイル

    このファイルは、システム内部設定なので、特に理由の無い限り変更しないこと
    ユーザ設定は、ブラウザから「ユーザ設定編集」で。管理者向け設定は、conf_admin.inc.phpで行う。
*/

$_conf['p2version'] = '1.8.56'; // rep2のバージョン

$_conf['p2name'] = 'rep2';    // rep2の名前。

$_conf['p2uaname'] = 'r e p 2';  // UA用のrep2の名前

//======================================================================
// 基本設定処理
//======================================================================
// エラー出力設定（NOTICE削減中。まだ残っていると思う）
if (defined('E_STRICT')) {
    error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}
//error_reporting(E_ALL & ~(E_NOTICE | E_STRICT | E_DEPRECATED));

// {{{ 基本変数

$_conf['p2web_url']             = 'http://akid.s17.xrea.com/';
$_conf['p2ime_url']             = 'http://akid.s17.xrea.com/p2ime.phtml';
$_conf['favrank_url']           = 'http://akid.s17.xrea.com/favrank/favrank.php';
$_conf['menu_php']              = 'menu.php';
$_conf['subject_php']           = 'subject.php'; // subject_i.php
$_conf['read_php']              = 'read.php';
$_conf['read_new_php']          = 'read_new.php';
$_conf['read_new_k_php']        = 'read_new_k.php';
$_conf['post_php']              = 'post.php';
$_conf['cookie_file_name']      = 'p2_cookie.txt';
$_conf['menu_k_php']            = 'menu_k.php'; // menu_i.php
$_conf['editpref_php']          = 'editpref.php'; // editpref_i.php

// info.php はJavaScriptファイル中に書かれているのが難

// }}}

// デバッグ
_setDebug(); // void  $GLOBALS['debug'], $GLOBALS['profiler']

// PHPの動作環境を確認
_checkPHPInstalled(); // void|die

// {{{ 環境設定

// タイムゾーンをセット
if (function_exists('date_default_timezone_set')) { 
    date_default_timezone_set('Asia/Tokyo'); 
} else { 
    @putenv('TZ=JST-9'); 
}

// メモリ制限値の下限設定(M)
// 設定値が指定値未満なら指定値に引き上げて設定する
_setMemoryLimit(32);

// スクリプトの実行時間制限の下限設定(秒)
// 設定値が指定秒未満なら指定秒に引き上げて設定する
_setTimeLimit(60);

// 自動フラッシュをオフにする
ob_implicit_flush(0);

// クライアントから接続を切られても処理を続行する
// ignore_user_abort(1);

// session.trans_sid有効時 や output_add_rewrite_var(), http_build_query() 等で生成・変更される
// URLのGETパラメータ区切り文字(列)を"&amp;"にする。（デフォルトは"&"）
ini_set('arg_separator.output', '&amp;');

// リクエストIDを設定
define('P2_REQUEST_ID', substr($_SERVER['REQUEST_METHOD'], 0, 1) . md5(serialize($_REQUEST)));

// OS別の定数をセットする。PATH_SEPARATOR, DIRECTORY_SEPARATOR
_setOSDefine();

// }}}


// 文字コードの指定
_setEncodings();

// {{{ ライブラリ類のパス設定

define('P2_CONF_DIR', dirname(__FILE__)); // __DIR__ @php-5.3

define('P2_BASE_DIR', dirname(P2_CONF_DIR));

// 基本的な機能を提供するライブラリ
define('P2_LIB_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib');

// おまけ的な機能を提供するライブラリ
define('P2EX_LIB_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'expack');

// スタイルシート
define('P2_STYLE_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'style');

// スキン
define('P2_SKIN_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'skin');

// PEARインストールディレクトリ、検索パスに追加される
define('P2_PEAR_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'includes');

// PEARをハックしたファイル用ディレクトリ、通常のPEARより優先的に検索パスに追加される
// Cache/Container/db.php(PEAR::Cache)がMySQL縛りだったので、汎用的にしたものを置いている
define('P2_PEAR_HACK_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pear_hack');


require_once P2_LIB_DIR . '/global.funcs.php';

// 検索パスをセット
_iniSetIncludePath(); // void

// PEARライブラリを読み込む
_includePears(); // void|die

require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'P2Util.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'DataPhp.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'Session.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'Login.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'UA.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'P2View.php';

// }}}
// {{{ PEAR::PHP_CompatでPHP5互換の関数を読み込む

if (version_compare(phpversion(), '5.0.0', '<')) {
    PHP_Compat::loadFunction('file_put_contents');
    //PHP_Compat::loadFunction('clone');
    PHP_Compat::loadFunction('scandir');
    //PHP_Compat::loadFunction('http_build_query'); // 第3引数に対応するまでは使えない
    //PHP_Compat::loadFunction('array_walk_recursive');
}

// }}}

// フォームからの入力（POST, GET）を一括で文字コード変換＆サニタイズ
_convertEncodingAndSanitizePostGet();

// 管理者用設定を読み込み
if (!include_once './conf/conf_admin.inc.php') {
    P2Util::printSimpleHtml("p2 error: 管理者用設定ファイルを読み込めませんでした。");
    die;
}

ini_set('default_socket_timeout', $_conf['fsockopen_time_limit']);

// 管理用保存ディレクトリ (パーミッションは707)
$_conf['admin_dir'] = $_conf['data_dir'] . '/admin';

// cache 保存ディレクトリ (パーミッションは707)
$_conf['cache_dir'] = $_conf['data_dir'] . '/cache'; // 2005/6/29 $_conf['pref_dir'] . '/p2_cache' より変更

// テンポラリディレクトリ (パーミッションは707)
$_conf['tmp_dir'] = $_conf['data_dir'] . '/tmp';

$_conf['accesskey_for_k'] = 'accesskey';

// {{{ 端末判定

$_conf['login_check_ip']  = 1; // 携帯ログイン時にIPアドレスを検証する

// 基本（PC）
$_conf['ktai'] = false;
$_conf['disable_cookie'] = false;

if (UA::isSafariGroup()) {
    $_conf['accept_charset'] = 'UTF-8';
} else {
    $_conf['accept_charset'] = 'Shift_JIS';
}

$mobile = &Net_UserAgent_Mobile::singleton();
if (PEAR::isError($mobile)) {
    trigger_error($mobile->toString(), E_USER_WARNING);

// UAが携帯なら
} elseif ($mobile and !$mobile->isNonMobile()) {

    require_once P2_LIB_DIR . '/HostCheck.php';
    
    $_conf['ktai'] = true;
    $_conf['accept_charset'] = 'Shift_JIS';

    // ベンダ判定
    // 2007/11/11 IPチェックは認証時に行った方がよさそうな
    // docomo i-Mode
    if ($mobile->isDoCoMo()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrDocomo()) {
            P2Util::printSimpleHtml("p2 error: UAがdocomoですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die;
        }
        $_conf['disable_cookie'] = true;
        
    // EZweb (au or Tu-Ka)
    } elseif ($mobile->isEZweb()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAu()) {
            P2Util::printSimpleHtml("p2 error: UAがEZwebですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die;
        }
        $_conf['disable_cookie'] = false;
        
    // SoftBank(旧Vodafone Live!)
    } elseif ($mobile->isSoftBank()) {
        //$_conf['accesskey_for_k'] = 'DIRECTKEY';
        // W型端末と3GC型端末はCookieが使える
        if ($mobile->isTypeW() || $mobile->isType3GC()) {
            $_conf['disable_cookie'] = false;
        } else {
            $_conf['disable_cookie'] = true;
        }
        if ($_conf['login_check_ip'] && !HostCheck::isAddrSoftBank()) {
            P2Util::printSimpleHtml("p2 error: UAがSoftBankですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die;
        }

    // WILLCOM（旧AirH"Phone）
    } elseif ($mobile->isWillcom()) {
        /*
        // WILLCOMでは端末ID認証を行わないので、コメントアウト
        if ($_conf['login_check_ip'] && !HostCheck::isAddrWillcom()) {
            P2Util::printSimpleHtml("p2 error: UAがAirH&quot;ですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
            die;
        }
        */
        $_conf['disable_cookie'] = false;
        
    // その他
    } else {
        $_conf['disable_cookie'] = true;
    }
}

// iPhone指定
if (UA::isIPhoneGroup()) {
    $_conf['ktai'] = true;
    UA::setForceMode(UA::getMobileQuery());

    define('P2_IPHONE_LIB_DIR', './iphone');

    $_conf['ktai']           = true;
    $_conf['subject_php']    = 'subject_i.php';
    $_conf['read_new_k_php'] = 'read_new_i.php';
    $_conf['menu_k_php']     = 'menu_i.php';
    $_conf['editpref_php']   = 'editpref_i.php';
}

// }}}
// {{{ クエリーによる強制ビュー指定

// b=pc はまだ全てのリンクに追加されておらず、機能しない場合がある。地道に整備していきたい。
// output_add_rewrite_var() は便利だが、出力がバッファされて体感速度が落ちるのが難点。。
// 体感速度を落とさない良い方法ないかな？

$b = UA::getQueryKey();

// ?k=1は旧仕様。?b=kが新しい。

// 後方互換用
if (!empty($_GET['k']) || !empty($_POST['k'])) {
    $_REQUEST[$b] = $_GET[$b] = 'k';
}

// $_conf[$b]（$_conf['b']） も使わないようにして、UA::getQueryValue()を利用する方向。
$_conf[$b] = UA::getQueryValue();

// $_conf['ktai'] は使わない方向。
// UA::isK(), UA::isPC() を利用する。

// 強制PCビュー指定（b=pc）
if (UA::isPCByQuery()) {
    $_conf['ktai'] = false;

// 強制携帯ビュー指定（b=k）
} elseif (UA::isMobileByQuery()) {
    $_conf['ktai'] = true;
}

// ↓k_at_a, k_at_q, k_input_ht は使わない方向。
// UA::getQueryKey(), UA::getQueryValue(), P2View::getInputHiddenKTag() を利用する。
$_conf['k_at_a'] = '';
$_conf['k_at_q'] = '';
$_conf['k_input_ht'] = '';
if ($_conf[$b]) {
    //output_add_rewrite_var($b, htmlspecialchars($_conf[$b], ENT_QUOTES));

    $b_hs = hs($_conf[$b]);
    $_conf['k_at_a'] = "&amp;{$b}={$b_hs}";
    $_conf['k_at_q'] = "?{$b}={$b_hs}";
    $_conf['k_input_ht'] = P2View::getInputHiddenKTag();
}

// }}}

// 2008/09/28 $_conf['k_to_index_ht'] は廃止して、P2View::getBackToIndexKATag() を利用
// $_conf['k_to_index_ht'] = sprintf('<a %s="0" href="index.php%s">0.TOP</a>', $_conf['accesskey_for_k'], $_conf['k_at_q']);


//======================================================================

// {{{ ユーザ設定 読込

// デフォルト設定（conf_user_def.inc.php）を読み込む
require_once './conf/conf_user_def.inc.php';
$_conf = array_merge($_conf, $conf_user_def);

// ユーザ設定があれば読み込む
$_conf['conf_user_file'] = $_conf['pref_dir'] . '/conf_user.srd.cgi';

// 2006-02-27 旧形式ファイルがあれば変換してコピー
_copyOldConfUserFileIfExists();

$conf_user = array();
if (file_exists($_conf['conf_user_file'])) {
    if ($cont = file_get_contents($_conf['conf_user_file'])) {
        $conf_user = unserialize($cont);
        $_conf = array_merge($_conf, $conf_user);
    }
}

// }}}

$_conf['conf_user_style_inc_php']    = "./conf/conf_user_style.inc.php";

// デザイン設定（$STYLE）読み込み

$_conf['skin_setting_path'] = $_conf['pref_dir'] . '/' . 'p2_user_skin.txt';
$_conf['skin_setting_perm'] = 0606;

_setStyle(); // $STYLE, $MYSTYLE

// {{{ デフォルト設定

isset($_conf['rct_rec_num'])         or $_conf['rct_rec_num']       = 20;
isset($_conf['res_hist_rec_num'])    or $_conf['res_hist_rec_num']  = 20;
isset($_conf['posted_rec_num'])      or $_conf['posted_rec_num']    = 1000;
isset($_conf['before_respointer'])   or $_conf['before_respointer'] = 20;
isset($_conf['sort_zero_adjust'])    or $_conf['sort_zero_adjust']  = 0.1;
isset($_conf['display_threads_num']) or $_conf['display_threads_num'] = 150;
isset($_conf['cmp_dayres_midoku'])   or $_conf['cmp_dayres_midoku'] = 1;
isset($_conf['k_sb_disp_range'])     or $_conf['k_sb_disp_range']   = 30;
isset($_conf['k_rnum_range'])        or $_conf['k_rnum_range']      = 10;
isset($_conf['pre_thumb_height'])    or $_conf['pre_thumb_height']  = '32';
isset($_conf['quote_res_view'])      or $_conf['quote_res_view']    = 1;
isset($_conf['res_write_rec'])       or $_conf['res_write_rec']     = 1;

isset($STYLE['post_pop_size'])       or $STYLE['post_pop_size'] = '610,350';
isset($STYLE['post_msg_rows'])       or $STYLE['post_msg_rows'] = 10;
isset($STYLE['post_msg_cols'])       or $STYLE['post_msg_cols'] = 70;
isset($STYLE['info_pop_size'])       or $STYLE['info_pop_size'] = '600,380';

// }}}
// {{{ ユーザ設定の調整処理

// $_conf['ext_win_target_at'], $_conf['bbs_win_target_at'] は使用せず廃止の方向で
$_conf['ext_win_target_at'] = '';
if ($_conf['ext_win_target']) {
    $_conf['ext_win_target_at'] = sprintf(' target="%s"',  htmlspecialchars($_conf['ext_win_target'], ENT_QUOTES));
}
/*
$_conf['bbs_win_target_at'] = '';
if ($_conf['bbs_win_target']) {
    $_conf['bbs_win_target_at'] = sprintf(' target="%s"',  htmlspecialchars($_conf['bbs_win_target'], ENT_QUOTES));
}
*/

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
    $_conf['k_filter_marker'] = "<font color=\"" . htmlspecialchars($_conf['mobile.match_color'], ENT_QUOTES) . '">\\0</font>';
} else {
    $_conf['k_filter_marker'] = null;
}


$_conf['output_callback'] = null;

// ob_start('mb_output_handler');

if (UA::isK() //&& $mobile && $mobile->isWillcom()
    // gzip可能かどうかはPHPで判別してくれるはず
    //&& !ini_get('zlib.output_compression') // サーバーの設定で自動gzip圧縮が有効になっていない
    //&& strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') // ブラウザがgzipをデコードできる
) {
    !defined('SID') || !strlen(SID) and $_conf['output_callback'] = 'ob_gzhandler';
}

// gzip圧縮すると逐次出力はできなさそう
//!defined('SID') || !strlen(SID) and $_conf['output_callback'] = 'ob_gzhandler';

if ($_conf['output_callback']) {
    ob_start($_conf['output_callback']);
}

// ob_gzhandler 利用時、バッファがある状態で、flush()してしまうとgzip転送にならなくなる。直前にob_flush()を入れるとOK。
//ob_flush();
//print_r(ob_list_handlers());
//print_r(getallheaders());

//======================================================================
// 変数設定
//======================================================================
// 最近読んだスレ
$_conf['recent_file']           = $_conf['pref_dir'] . '/p2_recent.idx';
// 互換用
$_conf['recent_idx'] = $_conf['recent_file'];

$_conf['res_hist_idx']      = $_conf['pref_dir'] . '/p2_res_hist.idx';      // 書き込みログ (idx)

// 書き込みログファイル（dat）
$_conf['p2_res_hist_dat']       = $_conf['pref_dir'] . '/p2_res_hist.dat';

// 書き込みログファイル（データPHP）旧
$_conf['p2_res_hist_dat_php']   = $_conf['pref_dir'] . '/p2_res_hist.dat.php';

// 書き込みログファイル（dat） セキュリティ通報用
$_conf['p2_res_hist_dat_secu']  = $_conf['pref_dir'] . '/p2_res_hist.secu.cgi';

$_conf['cookie_dir']            = $_conf['pref_dir'] . '/p2_cookie'; // cookie 保存ディレクトリ

$_conf['favlist_file']          = $_conf['pref_dir'] . '/p2_favlist.idx';
// 互換用
$_conf['favlist_idx'] = $_conf['favlist_file'];

$_conf['palace_file']           = $_conf['pref_dir'] . '/p2_palace.idx';
$_conf['favita_path']           = $_conf['pref_dir'] . '/p2_favita.brd';
$_conf['idpw2ch_php']           = $_conf['pref_dir'] . '/p2_idpw2ch.php';
$_conf['sid2ch_php']            = $_conf['pref_dir'] . '/p2_sid2ch.php';
$_conf['auth_user_file']        = $_conf['pref_dir'] . '/p2_auth_user.php';
$_conf['auth_ez_file']          = $_conf['pref_dir'] . '/p2_auth_ez.php';
$_conf['auth_jp_file']          = $_conf['pref_dir'] . '/p2_auth_jp.php';
$_conf['auth_docomo_file']      = $_conf['pref_dir'] . '/p2_auth_docomo.php';
$_conf['login_log_file']        = $_conf['pref_dir'] . '/p2_login.log.php';
$_conf['login_failed_log_file'] = $_conf['pref_dir'] . '/p2_login_failed.dat.php';

// saveMatomeCache() のために $_conf['pref_dir'] を絶対パスに変換する
define('P2_PREF_DIR_REAL_PATH', File_Util::realPath($_conf['pref_dir']));

$_conf['matome_cache_path'] = P2_PREF_DIR_REAL_PATH . DIRECTORY_SEPARATOR . 'matome_cache';
$_conf['matome_cache_ext'] = '.htm';
$_conf['matome_cache_max'] = 3; // 予備キャッシュの数

// 補正
if (
    version_compare(phpversion(), '5.0.0', '<')
    or $_conf['expack.use_pecl_http'] && !extension_loaded('http')
) {
    //if (!($_conf['expack.use_pecl_http'] == 2 && $_conf['expack.dl_pecl_http'])) {
        $_conf['expack.use_pecl_http'] = 0;
    //}
} elseif ($_conf['expack.use_pecl_http'] == 3 && UA::isK()) {
    $_conf['expack.use_pecl_http'] = 1;
}


// {{{ ありえない引数のエラー

// 新規ログインとメンバーログインの同時指定はありえないので、エラー出す
if (isset($_POST['submit_new']) && isset($_POST['submit_member'])) {
    P2Util::printSimpleHtml("p2 Error: 無効なURLです。");
    die;
}

// }}}
// {{{ ホストチェック

if ($_conf['secure']['auth_host'] || $_conf['secure']['auth_bbq']) {
    require_once P2_LIB_DIR . '/HostCheck.php';
    if (($_conf['secure']['auth_host'] && HostCheck::getHostAuth() == FALSE) ||
        ($_conf['secure']['auth_bbq'] && HostCheck::getHostBurned() == TRUE)
    ) {
        HostCheck::forbidden();
    }
}

// }}}

// セッションの開始
$_p2session = _startSession();

// ログインクラスのインスタンス生成（ログインユーザが指定されていなければ、この時点でログインフォーム表示に）
$_login = new Login;


//=============================================================================
// 関数（このファイル内でのみ利用）
//=============================================================================

/**
 * 再帰的にstripslashesをかける
 * GET/POST/COOKIE変数用なのでオブジェクトのプロパティには対応しない
 * (ExUtil)
 *
 * @return  array|string
 */
function stripslashesR($var, $r = 0)
{
    $rlimit = 10;
    if (is_array($var) && $r < $rlimit) {
        foreach ($var as $key => $value) {
            $var[$key] = stripslashesR($value, ++$r);
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
function nullfilterR($var, $r = 0)
{
    $rlimit = 10;
    if (is_array($var) && $r < $rlimit) {
        foreach ($var as $key => $value) {
            $var[$key] = nullfilterR($value, ++$r);
        }
    } elseif (is_string($var)) {
        $var = str_replace(chr(0), '', $var);
    }
    return $var;
}

/**
 * メモリの使用量を表示する
 *
 * @return  void
 */
function printMemoryUsage()
{
    $kb = memory_get_usage() / 1024;
    $kb = number_format($kb, 2, '.', '');
    
    echo 'Memory Usage: ' . $kb . 'KB';
}

/**
 * メモリ制限値の下限設定(M)
 * 設定値が指定値未満なら指定値に引き上げて設定する
 *
 * @return  void
 */
function _setMemoryLimit($least_memory_limit_m = 32)
{
    if (preg_match('/^(\\d+)M$/', ini_get('memory_limit'), $m)) {
        if ($m[1] < $least_memory_limit_m) {
            ini_set('memory_limit', $least_memory_limit_m . 'M');
        }
    }
}

/**
 * スクリプトの実行時間制限の下限設定(秒)
 * 設定値が指定秒未満なら指定秒に引き上げて設定する
 *
 * @return  void
 */
function _setTimeLimit($least_time_limit = 60)
{
    if ($t = ini_get('max_execution_time') and 0 < $t && $t < $least_time_limit) {
        if (!ini_get('safe_mode')) {
            set_time_limit($least_time_limit);
        }
    }
}

/**
 * @return  void  $GLOBALS['debug'], $GLOBALS['profiler']
 */
function _setDebug($debug = null)
{
    if (is_null($debug)) {
        $GLOBALS['debug'] = isset($_GET['debug']) ? intval($_GET['debug']) : 0;
    } else {
        $GLOBALS['debug'] = $debug;
    }
    if ($GLOBALS['debug']) {
        require_once 'Benchmark/Profiler.php';
        $GLOBALS['profiler'] = new Benchmark_Profiler(true);
        
        // 2007/08/03 Benchmark_Profiler 1.2.7 で _Benchmark_Profiler のPEAR式デストラクタがなくなって、
        // close() の手動メソッドになった？ので、手動で登録してみる。なんか変な気がするけど。
        if (!method_exists($GLOBALS['profiler'], '_Benchmark_Profiler') && method_exists($GLOBALS['profiler'], 'close')) {
            register_shutdown_function(array($GLOBALS['profiler'], 'close'));
        }

        // printMemoryUsage();
        register_shutdown_function('printMemoryUsage');
    }
}

/**
 * 文字コードの指定
 *
 * @return  void
 */
function _setEncodings()
{
    // mb_detect_order("SJIS-win,eucJP-win,ASCII");
    mb_internal_encoding('SJIS-win');
    mb_http_output('pass');
    mb_substitute_character(63); // 文字コード変換に失敗した文字が "?" になる
    //mb_substitute_character(0x3013); // 〓

    ini_set('default_mimetype', 'text/html');
    ini_set('default_charset', 'Shift_JIS');

    if (function_exists('mb_ereg_replace')) {
        define('P2_MBREGEX_AVAILABLE', 1);
        @mb_regex_encoding('SJIS-win');
    } else {
        define('P2_MBREGEX_AVAILABLE', 0);
    }
}

/**
 * 検索パスをセットする
 * P2_PEAR_HACK_DIR, P2_PEAR_DIR
 *
 * @return  void
 */
function _iniSetIncludePath()
{
    $include_path = '.';
    if (is_dir(P2_PEAR_HACK_DIR)) {
        $include_path .= PATH_SEPARATOR . realpath(P2_PEAR_HACK_DIR);
    }
    $include_path .= PATH_SEPARATOR . ini_get('include_path');
    if (is_dir(P2_PEAR_DIR)) {
        $include_path .= PATH_SEPARATOR . realpath(P2_PEAR_DIR);
    }
    //$include_path .= PATH_SEPARATOR . realpath(P2_LIB_DIR);
    ini_set('include_path', $include_path);
}

/**
 * PHPの動作環境を確認
 *
 * @return  void|die
 */
function _checkPHPInstalled()
{
    $errmsgs = array();
    if (version_compare(phpversion(), '4.3.0', 'lt')) {
        $errmsgs[] = 'PHPのバージョンが4.3.0未満では使えません。';
    }
    if (ini_get('safe_mode')) {
        $errmsgs[] = 'セーフモードで動作するPHPでは使えません。';
    }
    if (!extension_loaded('mbstring')) {
        $errmsgs[] = 'PHPのインストールが不十分です。PHPのmbstring拡張モジュールがロードされていません。';
    }
    if ($errmsgs) {
        $errmsgHtmls = array_map('htmlspecialchars', $errmsgs);
        die(sprintf(
            '<html><body><h3>p2 install error</h3><p>%s</p></body></html>',
            implode('<br>', $errmsgHtmls)
        ));
    }
}

/**
 * PEARライブラリを読み込む
 *
 * @return  void|die
 */
function _includePears()
{
    global $_conf;
    
    $requiredPears = array(
        'File/Util.php'             => 'File',
        'Net/UserAgent/Mobile.php'  => 'Net_UserAgent_Mobile',
        'PHP/Compat.php'            => 'PHP_Compat',
        'HTTP/Request.php'          => 'HTTP_Request'
    );
    foreach ($requiredPears as $pear_file => $pear_pkg) {
        if (!include_once($pear_file)) {
            $url = 'http://akid.s17.xrea.com/p2puki/pukiwiki.php?PEAR%A4%CE%A5%A4%A5%F3%A5%B9%A5%C8%A1%BC%A5%EB';
            $url_t = $_conf['p2ime_url'] . '?enc=1&url=' . rawurlencode($url);
            die(sprintf(
                '<html><body>
                <h3>p2 install error: PEAR の「%s」がインストールされていません</h3>
                <p><a href="%s" target="_blank">p2Wiki: PEARのインストール</a></p>
                </body></html>',
                hs($pear_pkg), hs($url_t)
            ));
        }
    }
}

/**
 * OS別の定数をセットする
 *
 * @return  void
 */
function _setOSDefine()
{
    // OS判定
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ';');
        defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '\\');
    } else {
        defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ':');
        defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '/');
    }
}

/**
 * @return  void
 */
function _setStyle()
{
    global $_conf, $STYLE, $MYSTYLE;
    
    // デフォルトCSS設定（$STYLE, $MYSTYLE）を読み込む
    include_once $_conf['conf_user_style_inc_php'];

    if ($_conf['skin'] = P2Util::getSkinSetting()) {
        // スキンで$STYLEを上書き
        $skinfile = P2Util::getSkinFilePathBySkinName($_conf['skin']);
        if (file_exists($skinfile)) {
            include_once $skinfile;
        }
    }

    // $STYLE設定の調整処理
    //if ($_SERVER['SCRIPT_NAME'] == 'css.php') {
        foreach ($STYLE as $k => $v) {
            if (empty($v)) {
                $STYLE[$k] = '';
            } elseif (strpos($k, 'fontfamily') !== false) {
                $STYLE[$k] = p2_correct_css_fontfamily($v);
            } elseif (strpos($k, 'color') !== false) {
                $STYLE[$k] = p2_correct_css_color($v);
            } elseif (strpos($k, 'background') !== false) {
                $STYLE[$k] = 'url("' . p2_escape_css_url($v) . '")';
            }
        }
    //}
}

/**
 * フォームからの入力を一括でクォート除去＆文字コード変換
 * フォームのaccept-encoding属性をUTF-8(Safari系) or Shift_JIS(その他)にし、
 * さらにhidden要素で美乳テーブルの文字を仕込むことで誤判定を減らす
 * 変換元候補にeucJP-winがあるのはHTTP入力の文字コードがEUCに自動変換されるサーバのため
 */
function _convertEncodingAndSanitizePostGet()
{
    if (!empty($_POST)) {
        if (get_magic_quotes_gpc()) {
            $_POST = array_map('stripslashesR', $_POST);
        }
        mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_POST);
        $_POST = array_map('nullfilterR', $_POST);
    }
    if (!empty($_GET)) {
        if (get_magic_quotes_gpc()) {
            $_GET = array_map('stripslashesR', $_GET);
        }
        mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_GET);
        $_GET = array_map('nullfilterR', $_GET);
    }
}

/**
 * 2006-02-27 旧形式ファイルがあれば変換してコピー
 *
 * @return  void
 */
function _copyOldConfUserFileIfExists()
{
    global $_conf;
    
    $conf_user_file_old = $_conf['pref_dir'] . '/conf_user.inc.php';
    if (!file_exists($_conf['conf_user_file']) && file_exists($conf_user_file_old)) {
        $old_cont = DataPhp::getDataPhpCont($conf_user_file_old);
        FileCtl::make_datafile($_conf['conf_user_file'], $_conf['conf_user_perm']);
        file_put_contents($_conf['conf_user_file'], $old_cont);
    }
}

/**
 * @return  Session|null
 */
function _startSession()
{
    global $_conf;
    
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
        if ($_conf['use_session'] == 1 or ($_conf['use_session'] == 2 && empty($_COOKIE['cid']))) { 
    
            // {{{ セッションデータ保存ディレクトリを設定
        
            if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {

                if (!is_dir($_conf['session_dir'])) {
                    require_once P2_LIB_DIR . '/FileCtl.php';
                    FileCtl::mkdirFor($_conf['session_dir'] . '/dummy_filename');
                } elseif (!is_writable($_conf['session_dir'])) {
                    die(sprintf(
                        'p2 error: セッションデータ保存ディレクトリ (%s) に書き込み権限がありません。',
                        hs($_conf['session_dir'])
                    ));
                }

                session_save_path($_conf['session_dir']);

                // session.save_path のパスの深さが2より大きいとガーベッジコレクションが行われないので
                // 自前でガーベッジコレクションする
                P2Util::session_gc();
            }
        
            // }}}

            return new Session;
        }
    //}
    
    return null;
}


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
