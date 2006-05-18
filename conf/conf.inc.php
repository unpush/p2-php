<?php
/*
    rep2 - 基本設定ファイル

    このファイルは、特に理由の無い限り変更しないこと
*/

$_conf['p2version'] = '1.7.26';     // rep2のバージョン
$_conf['p2expack'] = '060518.2348'; // ASAPのバージョン
$_conf['p2name'] = 'REP2EX-ASAP';   // rep2の名前。

//======================================================================
// 基本設定処理
//======================================================================
error_reporting(E_ALL & ~E_NOTICE); // エラー出力設定

// {{{ 基本変数

$_conf['p2web_url']             = 'http://akid.s17.xrea.com/';
$_conf['p2ime_url']             = 'http://akid.s17.xrea.com/p2ime.php';
$_conf['favrank_url']           = 'http://akid.s17.xrea.com:8080/favrank/favrank.php';
$_conf['expack.web_url']        = 'http://page2.xrea.jp/expack/';
$_conf['expack.download_url']   = 'http://page2.xrea.jp/expack/index.php/download';
$_conf['expack.history_url']    = 'http://page2.xrea.jp/expack/index.php/history#ASAP';
$_conf['expack.tgrep_url']      = 'http://page2.xrea.jp/tgrep/tgrep2-test.cgi';
$_conf['expack.ime_url']        = 'http://page2.xrea.jp/r.p';
$_conf['menu_php']              = 'menu.php';
$_conf['subject_php']           = 'subject.php';
$_conf['read_php']              = 'read.php';
$_conf['read_new_php']          = 'read_new.php';
$_conf['read_new_k_php']        = 'read_new_k.php';
$_conf['cookie_file_name']      = 'p2_cookie.txt';

$_info_msg_ht = ''; // ユーザ通知用 情報メッセージHTML

// }}}
// {{{ デバッグ

$debug = 0;
isset($_GET['debug']) and $debug = $_GET['debug'];

// }}}
// {{{ 動作環境を確認

$_php_version = phpversion();
$_required_version = '4.3.3';
$_recommended_version = (substr(zend_version(), 0, 1) == '1') ? '4.4.2' : '5.1.2';
if (version_compare($_php_version, $_required_version, '<')) {
    p2die('PHP ' . $_required_version . ' 未満では使えません。');
}
if (!extension_loaded('mbstring')) {
    p2die('PHPのインストールが不十分です。mbstring拡張モジュールがロードされていません。');
}
if (ini_get('safe_mode')) {
    p2die('セーフモードで動作するPHPでは使えません。');
}
if (ini_get('register_globals')) {
    $msg = <<<EOP
予期しない動作を避けるために php.ini で register_globals を Off にしてください。
magic_quotes_gpc や mbstring.encoding_translation も Off にされることをおすすめします。
EOP;
    p2die('register_globals が On です。', $msg);
}
if (true && version_compare($_php_version, $_recommended_version, '<')) {
    $_info_msg_ht .= '<p><b>古いバージョンのPHPで動作しています。</b> <i>(PHP ' . $_php_version . ')</i><br>';
    $_info_msg_ht .= 'PHP ' . $_recommended_version . ' 以降にアップデートすることをおすすめします。<br>';
    $_info_msg_ht .= '<small>（このメッセージを表示しないようにするには ' . htmlspecialchars(__FILE__, ENT_QUOTES) . ' の ';
    $_info_msg_ht .= (__LINE__ - 4) . ' 行目の &quot;true&quot; を &quot;false&quot; に書き換てください）</small></p>';
}
if (version_compare($_php_version, '5.1.0', '>=')) {
    define('P2_PHP50', true);
    define('P2_PHP51', true);
} elseif (version_compare($_php_version, '5.0.0', '>=')) {
    define('P2_PHP50', true);
    define('P2_PHP51', false);
} else {
    define('P2_PHP50', false);
    define('P2_PHP51', false);
}

// }}}
// {{{ 環境設定

// タイムゾーンをセット
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Tokyo');
} else {
    putenv('TZ=JST-9');
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
if (strstr(PHP_OS, 'WIN')) {
    // Windows
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
    $_include_path = '.';
    if (is_dir(P2_PEAR_HACK_DIR)) {
        $_include_path .= PATH_SEPARATOR . realpath(P2_PEAR_HACK_DIR);
    }
    if (is_dir(P2_PEAR_DIR)) {
        $_include_path .= PATH_SEPARATOR . realpath(P2_PEAR_DIR);
    }
    set_include_path($_include_path . PATH_SEPARATOR . get_include_path());
}

// ライブラリを読み込む
$_pear_required = array(
    'File/Util.php'             => 'File',
    'HTTP/Request.php'          => 'HTTP_Request',
    'Net/UserAgent/Mobile.php'  => 'Net_UserAgent_Mobile',
    'PHP/Compat.php'            => 'PHP_Compat',
);
if (!empty($debug)) {
    $_pear_required['Benchmark/Profiler.php'] = 'Benchmark';
}
foreach ($_pear_required as $_pear_file => $_pear_pkg) {
    if (!include_once($_pear_file)) {
        $url1 = 'http://akid.s17.xrea.com:8080/p2puki/pukiwiki.php?PEAR%A4%CE%A5%A4%A5%F3%A5%B9%A5%C8%A1%BC%A5%EB';
        $url2 = 'http://page2.xrea.jp/p2pear/index.php';
        $url1_t = P2Util::throughIme($url1);
        $url2_t = P2Util::throughIme($url2);
        $msg = <<<EOP
<ul>
    <li><a href="{$url1_t}" target="_blank">p2Wiki: PEARのインストール</a></li>
    <li><a href="{$url2_t}" target="_blank">p2pear (PEAR詰め合わせ)</a></li>
</ul>
EOP;
        p2die('PEAR の ' . $_pear_pkg . ' がインストールされていません。', $msg, true);
    }
}
require_once P2_LIBRARY_DIR . '/p2util.class.php';
require_once P2_LIBRARY_DIR . '/dataphp.class.php';
require_once P2_LIBRARY_DIR . '/session.class.php';
require_once P2_LIBRARY_DIR . '/login.class.php';

// }}}
// {{{ デバッグ

if (!empty($debug)) {
    $profiler =& new Benchmark_Profiler(true);
    // printMemoryUsage();
    register_shutdown_function('printMemoryUsage');
}

// }}}
// {{{ PEAR::PHP_CompatでPHP5互換の関数を読み込む

if (!P2_PHP50) {
    PHP_Compat::loadFunction('array_walk_recursive');
    PHP_Compat::loadFunction('clone');
    PHP_Compat::loadFunction('file_put_contents');
    PHP_Compat::loadFunction('http_build_query');
    PHP_Compat::loadFunction('scandir');
}

// }}}
// {{{ フォームからの入力を一括でサニタイズ

/**
 * フォームからの入力を一括でクォート除去＆文字コード変換
 * フォームのaccept-encoding属性をUTF-8(Safari系) or Shift_JIS(その他)にし、
 * さらにhidden要素で美乳テーブルの文字を仕込むことで誤判定を減らす
 * 変換元候補にeucJP-winがあるのはHTTP入力の文字コードがEUCに自動変換されるサーバのため
 */
if (!empty($_GET)) {
    if (get_magic_quotes_gpc()) {
        $_GET = array_map('stripslashes_r', $_GET);
    }
    mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_GET);
    $_GET = array_map('nullfilter_r', $_GET);
}
if (!empty($_POST)) {
    if (get_magic_quotes_gpc()) {
        $_POST = array_map('stripslashes_r', $_POST);
    }
    mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $_POST);
    $_POST = array_map('nullfilter_r', $_POST);
    $_REQUEST = array_merge($_GET, $_POST);
} else {
    $_REQUEST = $_GET;
}

// }}}

// ■管理者用設定を読み込み
if (!include_once './conf/conf_admin.inc.php') {
    p2die('管理者用設定ファイルを読み込めませんでした。');
}

// 管理用保存ディレクトリ (パーミッションは707)
$_conf['admin_dir'] = $_conf['data_dir'] . '/admin';

// cache 保存ディレクトリ (パーミッションは707)
$_conf['cache_dir'] = $_conf['data_dir'] . '/cache'; // 2005/6/29 $_conf['pref_dir'] . '/p2_cache' より変更

$_conf['doctype'] = '';
$_conf['accesskey'] = 'accesskey';

$_conf['meta_charset_ht'] = '<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">';

// {{{ 端末判定

$_conf['login_check_ip']  = 1; // ログイン時にIPアドレスを検証する
$_conf['input_type_search'] = FALSE;

$mobile = &Net_UserAgent_Mobile::singleton();

// PC
if ($mobile->isNonMobile()) {
    $_conf['ktai'] = FALSE;
    $_conf['disable_cookie'] = FALSE;

    if (P2Util::isBrowserSafariGroup()) {
        $_conf['accept_charset'] = 'UTF-8';
        $_conf['input_type_search'] = TRUE;
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
            p2die("UAがDoCoMoですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        $_conf['disable_cookie'] = TRUE;
    // EZweb (au or Tu-Ka)
    } elseif ($mobile->isEZweb()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAu()) {
            p2die("UAがEZwebですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        $_conf['disable_cookie'] = FALSE;
    // Vodafone Live!
    } elseif ($mobile->isVodafone()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrVodafone()) {
            p2die("UAがVodafoneですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        //$_conf['accesskey'] = 'DIRECTKEY';
        // W型端末と3GC型端末はCookieが使える
        if ($mobile->isTypeW() || $mobile->isType3GC()) {
            $_conf['disable_cookie'] = FALSE;
        } else {
            $_conf['disable_cookie'] = TRUE;
        }
    // AirH" Phone
    } elseif ($mobile->isAirHPhone()) {
        /*
        // AirH"では端末ID認証を行わないので、コメントアウト
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAirh()) {
            p2die("UAがAirH\"ですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
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

$_conf['view_forced_by_query'] = false;
$_conf['k_at_a'] = '';
$_conf['k_at_q'] = '';
$_conf['k_input_ht'] = '';

// 強制PCビュー指定
if ($_GET['b'] == 'pc' || $_POST['b'] == 'pc') {
    if ($_conf['ktai']) {
        $_conf['view_forced_by_query'] = true;
        $_conf['ktai'] = false;
    }
    $_conf['b'] = 'pc';
    //output_add_rewrite_var('b', 'pc');

    $_conf['k_at_a'] = '&amp;b=pc';
    $_conf['k_at_q'] = '?b=pc';
    $_conf['k_input_ht'] = '<input type="hidden" name="b" value="pc">';

// 強制携帯ビュー指定（b=k。k=1は過去互換用）
} elseif (!empty($_GET['k']) || !empty($_POST['k']) || $_GET['b'] == 'k' || $_POST['b'] == 'k') {
    if (!$_conf['ktai']) {
        $_conf['view_forced_by_query'] = true;
        $_conf['ktai'] = true;
    }
    $_conf['b'] = 'k';
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

// {{{ ■ユーザ設定 読込

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

// }}}
// {{{ ユーザ設定の調整処理

$_conf['ext_win_target_at'] = ($_conf['ext_win_target']) ? " target=\"{$_conf['ext_win_target']}\"" : "";
$_conf['bbs_win_target_at'] = ($_conf['bbs_win_target']) ? " target=\"{$_conf['bbs_win_target']}\"" : "";

if ($_conf['get_new_res']) {
    if ($_conf['get_new_res'] == 'all') {
        $_conf['get_new_res_l'] = $_conf['get_new_res'];
    } else {
        $_conf['get_new_res_l'] = 'l'.$_conf['get_new_res'];
    }
} else {
    $_conf['get_new_res_l'] = 'l200';
}

if ($_conf['expack.user_agent']) {
    ini_set('user_agent', $_conf['expack.user_agent']);
}

// }}}
// {{{ デザイン設定 読込

$skin_name = 'conf_user_style';
$skin = 'conf/conf_user_style.inc.php';
if (!$_conf['ktai'] && $_conf['expack.skin.enabled']) {
    if (file_exists($_conf['expack.skin.setting_path'])) {
        $skin_name = rtrim(file_get_contents($_conf['expack.skin.setting_path']));
        $skin = 'skin/' . $skin_name . '.php';
    } else {
        require_once P2_LIBRARY_DIR . '/filectl.class.php';
        FileCtl::make_datafile($_conf['expack.skin.setting_path'], $_conf['expack.skin.setting_perm']);
    }
    if (isset($_REQUEST['skin']) && preg_match('/^\w+$/', $_REQUEST['skin']) && $skin_name != $_REQUEST['skin']) {
        $skin_name = $_REQUEST['skin'];
        $skin = 'skin/' . $skin_name . '.php';
        FileCtl::file_write_contents($_conf['expack.skin.setting_path'], $skin_name);
    }
}
if (!file_exists($skin)) {
    $skin_name = 'conf_user_style';
    $skin = 'conf/conf_user_style.inc.php';
}
$skin_en = urlencode($skin_name);
include_once $skin;

// }}}
// {{{ デザイン設定の調整処理

if (!is_array($STYLE)) {
    $STYLE = array();
}

if (!isset($STYLE['post_pop_size'])) { $STYLE['post_pop_size'] = "610,350"; }
if (!isset($STYLE['post_msg_rows'])) { $STYLE['post_msg_rows'] = 10; }
if (!isset($STYLE['post_msg_cols'])) { $STYLE['post_msg_cols'] = 70; }
if (!isset($STYLE['info_pop_size'])) { $STYLE['info_pop_size'] = "600,380"; }

if (!isset($STYLE['mobile_subject_newthre_color'])) { $STYLE['mobile_subject_newthre_color'] = "#ff0000"; }
if (!isset($STYLE['mobile_subject_newres_color']))  { $STYLE['mobile_subject_newres_color']  = "#ff6600"; }
if (!isset($STYLE['mobile_read_ttitle_color']))     { $STYLE['mobile_read_ttitle_color']     = "#1144aa"; }
if (!isset($STYLE['mobile_read_newres_color']))     { $STYLE['mobile_read_newres_color']     = "#ff6600"; }
if (!isset($STYLE['mobile_read_ngword_color']))     { $STYLE['mobile_read_ngword_color']     = "#bbbbbb"; }
if (!isset($STYLE['mobile_read_onthefly_color']))   { $STYLE['mobile_read_onthefly_color']   = "#00aa00"; }

$skin_etag = $_conf['p2expack'];
fontconfig_apply_custom();

foreach ($STYLE as $K => $V) {
    if (empty($V)) {
        $STYLE[$K] = '';
    } elseif (strpos($K, 'fontfamily') !== FALSE) {
        $STYLE[$K] = set_css_fonts($V);
    } elseif (strpos($K, 'color') !== FALSE) {
        $STYLE[$K] = set_css_color($V);
    } elseif (strpos($K, 'background') !== FALSE) {
        $STYLE[$K] = 'url("' . addslashes($V) . '")';
    }
}
if (!$_conf['ktai'] && $_conf['expack.am.enabled']) {
    $_conf['expack.am.fontfamily'] = set_css_fonts($_conf['expack.am.fontfamily']);
    if ($STYLE['fontfamily']) {
        $_conf['expack.am.fontfamily'] .= '","' . $STYLE['fontfamily'];
    }
}

$_conf['k_colors'] = '';
if ($_conf['ktai']) {
    if ($_conf['mobile.background_color']) {
        $_conf['k_colors'] .= " bgcolor=\"{$_conf['mobile.background_color']}\"";
    }
    if ($_conf['mobile.text_color']) {
        $_conf['k_colors'] .= " text=\"{$_conf['mobile.text_color']}\"";
    }
    if ($_conf['mobile.link_color']) {
        $_conf['k_colors'] .= " link=\"{$_conf['mobile.link_color']}\"";
    }
    if ($_conf['mobile.vlink_color']) {
        $_conf['k_colors'] .= " vlink=\"{$_conf['mobile.vlink_color']}\"";
    }
    if ($_conf['mobile.newthre_color']) {
        $STYLE['mobile_subject_newthre_color'] = $_conf['mobile.newthre_color'];
    }
    if ($_conf['mobile.newres_color']) {
        $STYLE['mobile_read_newres_color']    = $_conf['mobile.newres_color'];
        $STYLE['mobile_subject_newres_color'] = $_conf['mobile.newres_color'];
    }
    if ($_conf['mobile.ttitle_color']) {
        $STYLE['mobile_read_ttitle_color'] = $_conf['mobile.ttitle_color'];
    }
    if ($_conf['mobile.ngword_color']) {
        $STYLE['mobile_read_ngword_color'] = $_conf['mobile.ngword_color'];
    }
    if ($_conf['mobile.onthefly_color']) {
        $STYLE['mobile_read_onthefly_color'] = $_conf['mobile.onthefly_color'];
    }
    // 携帯用マーカー
    if ($_conf['mobile.match_color']) {
        $_conf['k_filter_marker'] = "<font color=\"{$_conf['mobile.match_color']}\">\\1</font>";
    } else {
        $_conf['k_filter_marker'] = FALSE;
    }
}

// }}}

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
    p2die('無効なURLです。');
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
// {{{ ■セッション

// 名前は、セッションクッキーを破棄するときのために、セッション利用の有無に関わらず設定する
session_name('PS');

// eAcceleratorのセッションハンドラを使ってみる
/*if (extension_loaded('eAccelerator')) {
    eaccelerator_set_session_handlers();
}*/

// SQLiteのセッションハンドラを使ってみる
/*if (extension_loaded('sqlite')) {
    ob_start();
    phpinfo(INFO_MODULES);
    $_phpinfo_modules = ob_get_clean();
    $_sh_regex = '!<tr><td class="e">Registered save handlers *</td><td class="v">(.+?)</td></tr>!';
    if (preg_match($_sh_regex, $_phpinfo_modules, $_phpinfo_matches)
        && strstr($_phpinfo_matches[1], 'sqlite'))
    {
        session_module_name('sqlite');
        session_save_path(P2_PREF_DIR_REAL_PATH . DIRECTORY_SEPARATOR . 'p2_session.db');
    }
    unset($_sh_regex, $_phpinfo_modules, $_phpinfo_matches);
}*/

// {{{ セッションデータ保存ディレクトリを規定

if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {

    // $_conf['data_dir'] を絶対パスに変換する
    define('P2_DATA_DIR_REAL_PATH', File_Util::realPath($_conf['data_dir']));
    
    $_conf['session_dir'] = P2_DATA_DIR_REAL_PATH . DIRECTORY_SEPARATOR . 'session';
}

// }}}

if (defined('P2_FORCE_USE_SESSION') || $_conf['expack.misc.multi_favs']) {
    $_conf['use_session'] = 1;
}
if ($_conf['use_session'] == 1 or ($_conf['use_session'] == 2 && !$_COOKIE['cid'])) { 

    // {{{ セッションデータ保存ディレクトリを設定
    
    if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
    
        if (!is_dir($_conf['session_dir'])) {
            require_once P2_LIBRARY_DIR . '/filectl.class.php';
            FileCtl::mkdir_for($_conf['session_dir'] . '/dummy_filename');
        } elseif (!is_writable($_conf['session_dir'])) {
            p2die("セッションデータ保存ディレクトリ ({$_conf['session_dir']}) に書き込み権限がありません。");
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

// }}}

// お気にセットを切り替える
if ($_conf['expack.misc.multi_favs']) {
    require_once P2_LIBRARY_DIR . '/favsetmng.class.php';
    FavSetManager::switchFavSet();
}

// ■ログインクラスのインスタンス生成（ログインユーザが指定されていなければ、この時点でログインフォーム表示に）
@require_once P2_LIBRARY_DIR . '/login.class.php';
$_login =& new Login();


//=====================================================================
// 関数
//=====================================================================
/**
 * 再帰的にstripslashesをかける
 * GET/POST/COOKIE変数用なのでオブジェクトのプロパティには対応しない
 * (ExUtil)
 */
function stripslashes_r($var, $r = 0)
{
    if (is_array($var)) {
        if ($r < 3) {
            $r++;
            foreach ($var as $key => $value) {
                $var[$key] = stripslashes_r($value, $r);
            }
        } /* else { p2die("too deep multi dimentional array given."); } */
    } elseif (is_string($var)) {
        $var = stripslashes($var);
    }
    return $var;
}

/**
 * 再帰的にaddslashesをかける
 * (ExUtil)
 */
function addslashes_r($var, $r = 0)
{
    if (is_array($var)) {
        if ($r < 3) {
            $r++;
            foreach ($var as $key => $value) {
                $var[$key] = addslashes_r($value, $r);
            }
        } /* else { p2die("too deep multi dimentional array given."); } */
    } elseif (is_string($var)) {
        $var = addslashes($var);
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
    if (is_array($var)) {
        if ($r < 3) {
            $r++;
            foreach ($var as $key => $value) {
                $var[$key] = nullfilter_r($value, $r);
            }
        } /* else { p2die("too deep multi dimentional array given."); } */
    } elseif (is_string($var)) {
        $var = str_replace("\x00", '', $var);
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
    if (function_exists('memory_get_usage')) {
        $usage = memory_get_usage();
    } elseif (function_exists('xdebug_memory_usage')) {
        $usage = xdebug_memory_usage();
    } else {
        $usage = -1;
    }
    $kb = $usage / 1024;
    $kb = number_format($kb, 2, '.', '');
    
    echo 'Memory Usage: ' . $kb . 'KB';
}

/**
 * SI単位系の値を整数に変換する
 * 厳密には1000倍するのが正しいが、PC界隈 (記憶装置除く) の慣例に従って1024倍する
 */
function si2int($num, $kmg)
{
    return si2real($num, $kmg);
}
function si2real($num, $kmg)
{
    $num = (float)$num;
    switch (strtoupper($kmg)) {
        case 'G': $num *= 1024;
        case 'M': $num *= 1024;
        case 'K': $num *= 1024;
    }
    return $num;
}

/**
 * マルチバイト対応のbasename()
 */
function mb_basename($path, $encoding = 'SJIS-win')
{
    if (!mb_substr_count($path, '/', $encoding)) {
        return $path;
    }
    $len = mb_strlen($path, $encoding);
    $pos = mb_strrpos($path, '/', $encoding);
    return mb_substr($path, $pos + 1, $len - $pos, $encoding);
}

/**
 * フォント設定用にユーザエージェントを判定する
 */
function fontconfig_detect_agent($ua = null)
{
    if ($ua === null) {
        $ua = $_SERVER['HTTP_USER_AGENT'];
    }
    if (preg_match('/\bWindows\b/', $ua)) {
        return 'windows';
    }
    if (preg_match('/\bMac(intoth)?\b/', $ua)) {
        if (preg_match('/\b(Safari|AppleWebKit)\/(\d+(\.\d+)?)\b/', $ua, $matches)) {
            if (400 < (float) $matches[2]) {
                return 'safari2';
            } else {
                return 'safari1';
            }
        } elseif (preg_match('/\b(Mac ?OS ?X)\b/', $ua)) {
            return 'macosx';
        } else {
            return 'macos9';
        }
    }
    return 'other';
}

/**
 * フォント設定を読み込む
 */
function fontconfig_apply_custom()
{
    global $STYLE, $_conf, $skin_en, $skin_etag;
    if ($_conf['expack.skin.enabled']) {
        $_conf['expack.am.fontfamily.orig'] = (isset($_conf['expack.am.fontfamily']))
            ? $_conf['expack.am.fontfamily'] : '';
        $type = fontconfig_detect_agent();
        if (file_exists($_conf['expack.skin.fontconfig_path'])) {
            $fontconfig_data = file_get_contents($_conf['expack.skin.fontconfig_path']);
            $current_fontconfig = unserialize($fontconfig_data);
        }
        if (!is_array($current_fontconfig)) {
            $current_fontconfig = array('enabled' => false, 'custom' => array());
        }
        if ($current_fontconfig['enabled'] && is_array($current_fontconfig['custom'][$type])) {
            $skin_etag = '';
            $sha1 = sha1($fontconfig_data . $_conf['p2expack']);
            for ($i = 0; $i < 40; $i +=5) {
                $skin_etag .= base_convert(substr($sha1, $i, 5), 16, 32);
            }
            foreach ($current_fontconfig['custom'][$type] as $key => $value) {
                if (strstr($key, 'fontfamily') && $value == '-') {
                    if ($key == 'fontfamily_aa') {
                        $_conf['expack.am.fontfamily'] = '';
                    } else {
                        $STYLE["{$key}.orig"] = (isset($STYLE[$key])) ? $STYLE[$key] : '';
                        $STYLE[$key] = '';
                    }
                } elseif ($value) {
                    if ($key == 'fontfamily_aa') {
                        $_conf['expack.am.fontfamily'] = $value;
                    } else {
                        $STYLE["{$key}.orig"] = (isset($STYLE[$key])) ? $STYLE[$key] : '';
                        $STYLE[$key] = $value;
                    }
                }
            }
        }
    }
    $skin_en = preg_replace('/&amp;etag=[^&]*/', '', $skin_en);
    $skin_en .= '&amp;etag=' . urlencode($skin_etag);
}

/**
 * スタイルシートを読み込むタグを表示
 */
function print_style_tags()
{
    global $skin_name, $skin_etag;
    $style_a = '';
    if ($skin_name) { $style_a .= '&skin=' . urlencode($skin_name); }
    if ($skin_etag) { $style_a .= '&etag=' . urlencode($skin_etag); }
    if ($styles = func_get_args()) {
        echo "\t<style type=\"text/css\">\n";
        echo "\t<!-->\n";
        foreach ($styles as $style) {
            if (file_exists(P2_STYLE_DIR . '/' . $style . '_css.inc')) {
                printf("\t@import 'css.php?css=%s%s';\n", $style, $style_a);
            }
        }
        echo "\t-->\n";
        echo "\t</style>\n";
    }
}

/**
 * スタイルシートのフォント指定を調整する
 */
function set_css_fonts($fonts)
{
    if (is_string($fonts)) {
        $fonts = preg_split('/(["\'])?\\s*,\\s*(?(1)\\1)/', trim($fonts, " \t\"'"));
    } elseif (!is_array($fonts)) {
        return '';
    }
    $fonts = '"' . implode('","', $fonts) . '"';
    $fonts = preg_replace('/"(serif|sans-serif|cursive|fantasy|monospace)"/', '$1', $fonts);
    return trim($fonts, '"');
}

/**
 * スタイルシートの色指定を調整する
 */
function set_css_color($color)
{
    return preg_replace('/^#([0-9A-F])([0-9A-F])([0-9A-F])$/i', '#$1$1$2$2$3$3', $color);
}

/**
 * Safari からアップロードされたファイル名の濁音・半濁音にマッチする正規表現
 */
$GLOBALS['COMBINEHFSKANA_REGEX'] = str_replace(
    array('%u3099%', '%u309A%'),
    array(pack('C*', 0xE3, 0x82, 0x99), pack('C*', 0xE3, 0x82, 0x9A)),
    mb_convert_encoding(
        '/([うか-こさ-そた-とは-ほウカ-コサ-ソタ-トハ-ホゝヽ])%u3099%|([は-ほハ-ホ])%u309A%/u',
        'UTF-8', 'SJIS-win'));

/**
 * Safari からアップロードされたファイル名の文字化けを補正する関数
 * 清音+濁点・清音+半濁点を一文字にまとめる (NFD で正規化された かな を NFC にする)
 * 入出力の文字コードはUTF-8
 */
function combinehfskana($str)
{
    return preg_replace_callback($GLOBALS['COMBINEHFSKANA_REGEX'], '_combinehfskana', $str);
}

function _combinehfskana($m)
{
    if ($m[1]) {
        $C = unpack('C*', $m[1]);
        $C[3] += 1;
    } elseif ($m[2]) {
        $C = unpack('C*', $m[2]);
        $C[3] += 2;
    }
    return pack('C*', $C[1], $C[2], $C[3]);
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
 */
function wakati($str)
{
    return array_filter(array_map('trim', preg_split($GLOBALS['WAKATI_REGEX'],
        mb_strtolower(mb_convert_kana(mb_convert_encoding(
            $str, 'UTF-8', 'SJIS-win'), 'KVas', 'UTF-8'), 'UTF-8'),
        -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)), 'strlen');
}

/**
 * メッセージを表示して終了
 */
function p2die($err, $msg = null, $raw = false)
{
    echo '<html><head><title>p2 error</title></head><body>';
    echo '<h3>p2 error: ', htmlspecialchars($err, ENT_QUOTES), '</h3>';
    if ($msg !== null) {
        if ($raw) {
            echo '<p>', nl2br(htmlspecialchars($msg, ENT_QUOTES)), '</p>';
        } else {
            echo $msg;
        }
    }
    echo '</body></html>';
}

?>
