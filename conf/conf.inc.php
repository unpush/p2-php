<?php
/**
 * rep2 - 基本設定ファイル
 * このファイルは、特に理由の無い限り変更しないこと
 */

// バージョン情報
$_conf = array(
    'p2version' => '1.7.29',        // rep2のバージョン
    'p2expack'  => '080827.0000',   // 拡張パックのバージョン
    'p2name'    => 'expack',        // rep2の名前
);

define('P2_VERSION_ID', sprintf('%u', crc32($_conf['p2version'] . ';' . $_conf['p2expack'])));

// {{{ グローバル変数を初期化

$_info_msg_ht = ''; // ユーザ通知用 情報メッセージHTML

$MYSTYLE    = array();
$STYLE      = array();
$debug      = false;
$skin       = null;
$skin_en    = null;
$skin_name  = null;
$skin_uniq  = null;
$_login     = null;
$_p2session = null;

$conf_user_def   = array();
$conf_user_rules = array();
$conf_user_rad   = array();
$conf_user_sel   = array();

// }}}

// 基本設定処理を実行
p2configure();

// クリーンアップ
if (basename($_SERVER['SCRIPT_NAME']) != 'edit_conf_user.php') {
    unset($conf_user_def, $conf_user_rules, $conf_user_rad, $conf_user_sel);
}

// E_NOTICE および暗黙の配列初期化除け
$_conf['filtering'] = false;
$hd = array('word' => null);
$htm = array();
$word = null;

// {{{ p2configure()

/**
 * 一時変数でグローバル変数を汚染しないように設定処理を関数化
 */
function p2configure()
{
    global $MYSTYLE, $STYLE, $debug;
    global $skin, $skin_en, $skin_name, $skin_uniq;
    global $_conf, $_info_msg_ht, $_login, $_p2session;
    global $conf_user_def, $conf_user_rules, $conf_user_rad, $conf_user_sel;

// エラー出力設定
//error_reporting(E_ALL & ~E_STRICT);
error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));
//error_reporting(E_ALL & ~(E_NOTICE | E_STRICT | E_DEPRECATED));

// 新規ログインとメンバーログインの同時指定はありえないので、エラーを出す
if (isset($_POST['submit_new']) && isset($_POST['submit_member'])) {
    p2die('無効なURLです。');
}

// {{{ 基本変数

$_conf['p2web_url']             = 'http://akid.s17.xrea.com/';
$_conf['p2ime_url']             = 'http://akid.s17.xrea.com/p2ime.php';
$_conf['favrank_url']           = 'http://akid.s17.xrea.com/favrank/favrank.php';
$_conf['expack.web_url']        = 'http://page2.xrea.jp/expack/';
$_conf['expack.download_url']   = 'http://page2.xrea.jp/expack/index.php/download';
$_conf['expack.history_url']    = 'http://page2.xrea.jp/expack/index.php/history#ASAP';
$_conf['expack.tgrep_url']      = 'http://page2.xrea.jp/tgrep/search';
$_conf['expack.ime_url']        = 'http://page2.xrea.jp/r.p';
$_conf['menu_php']              = 'menu.php';
$_conf['subject_php']           = 'subject.php';
$_conf['read_php']              = 'read.php';
$_conf['read_new_php']          = 'read_new.php';
$_conf['read_new_k_php']        = 'read_new_k.php';
$_conf['cookie_file_name']      = 'p2_cookie.txt';

// }}}
// {{{ 環境設定

// 動作環境を確認 (要件を満たしているならコメントアウト可)
p2checkenv(__LINE__);

// デバッグ
//$debug = !empty($_GET['debug']);

// タイムゾーンをセット
date_default_timezone_set('Asia/Tokyo');

set_time_limit(60); // (60) スクリプト実行制限時間(秒)

// 自動フラッシュをオフにする
ob_implicit_flush(0);

// クライアントから接続を切られても処理を続行する
// ignore_user_abort(1);

// file($filename, FILE_IGNORE_NEW_LINES) で CR/LF/CR+LF のいずれも行末として扱う
ini_set('auto_detect_line_endings', 1);

// session.trans_sid有効時 や output_add_rewrite_var(), http_build_query() 等で生成・変更される
// URLのGETパラメータ区切り文字(列)を"&amp;"にする。（デフォルトは"&"）
ini_set('arg_separator.output', '&amp;');

// リクエストIDを設定 (コストが大きい割に使っていないので廃止)
//define('P2_REQUEST_ID', substr($_SERVER['REQUEST_METHOD'], 0, 1) . md5(serialize($_REQUEST)));

// Windows なら
if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
    // Windows
    defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ';');
    defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '\\');
    define('P2_OS_WINDOWS', 1);
} else {
    defined('PATH_SEPARATOR') or define('PATH_SEPARATOR', ':');
    defined('DIRECTORY_SEPARATOR') or define('DIRECTORY_SEPARATOR', '/');
    define('P2_OS_WINDOWS', 0);
}

// }}}
// {{{ P2Util::header_content_type() を不要にするおまじない

ini_set('default_mimetype', 'text/html');
ini_set('default_charset', 'Shift_JIS');

// }}}
// {{{ 文字コードの指定

//mb_detect_order("CP932,CP51932,ASCII");
mb_internal_encoding('CP932');
mb_http_output('pass');
mb_substitute_character(63); // 文字コード変換に失敗した文字が "?" になる
//mb_substitute_character(0x3013); // 〓
//ob_start('mb_output_handler');

if (function_exists('mb_ereg_replace')) {
    define('P2_MBREGEX_AVAILABLE', 1);
    mb_regex_encoding('CP932');
} else {
    define('P2_MBREGEX_AVAILABLE', 0);
}

// }}}
// {{{ ライブラリ類のパス設定

define('P2_BASE_DIR', dirname(dirname(__FILE__)));

// 基本的な機能を提供するするライブラリ
define('P2_LIB_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib');

// おまけ的な機能を提供するするライブラリ
define('P2EX_LIB_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'expack');

// スタイルシート
define('P2_STYLE_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR .  'style');

// PEARインストールディレクトリ、検索パスに追加される
define('P2_PEAR_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'includes');

// PEARをハックしたファイル用ディレクトリ、通常のPEARより優先的に検索パスに追加される
// Cache/Container/db.php(PEAR::Cache)がMySQL縛りだったので、汎用的にしたものを置いている
define('P2_PEAR_HACK_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pear_hack');

// 検索パスをセット
$include_path = P2_BASE_DIR;
if (is_dir(P2_PEAR_HACK_DIR)) {
    $include_path .= PATH_SEPARATOR . P2_PEAR_HACK_DIR;
}
if (is_dir(P2_PEAR_DIR)) {
    $include_path .= PATH_SEPARATOR . P2_PEAR_DIR;
}
$include_path .= PATH_SEPARATOR . get_include_path();
set_include_path($include_path);

// ライブラリを読み込む
/*
$_pear_required = array(
    'File/Util.php'             => 'File',
    'HTTP/Request.php'          => 'HTTP_Request',
    'Net/UserAgent/Mobile.php'  => 'Net_UserAgent_Mobile',
);
if ($debug) {
    $_pear_required['Benchmark/Profiler.php'] = 'Benchmark';
}

foreach ($_pear_required as $_pear_file => $_pear_pkg) {
    if (!include_once($_pear_file)) {
        $url1 = 'http://akid.s17.xrea.com/p2puki/pukiwiki.php?PEAR%A4%CE%A5%A4%A5%F3%A5%B9%A5%C8%A1%BC%A5%EB';
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
*/

require_once 'Net/UserAgent/Mobile.php';
require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/p2util.class.php';
require_once P2_LIB_DIR . '/dataphp.class.php';
require_once P2_LIB_DIR . '/session.class.php';
require_once P2_LIB_DIR . '/login.class.php';

// }}}
// {{{ デバッグ

if ($debug) {
    require_once 'Benchmark/Profiler.php';
    $profiler = new Benchmark_Profiler(true);
    // printMemoryUsage();
    register_shutdown_function('printMemoryUsage');
}

// }}}
// {{{ リクエスト変数の処理

/**
 * リクエスト変数を一括でクォート除去＆文字コード変換
 *
 * 日本語を入力する可能性のあるフォームには隠し要素で
 * エンコーディング判定用の文字列を仕込んでいる
 *
 * $_COOKIE は $_REQUEST に含めない
 */
if (!empty($_GET) || !empty($_POST)) {
    if (isset($_REQUEST['_hint'])) {
        // "CP932" は "SJIS-win" のエイリアスで、"SJIS-win" と "SJIS" は別物
        // "CP51932", "eucJP-win", "EUC-JP" はそれぞれ別物 (libmbfl的な意味で)
        $request_encoding = mb_detect_encoding($_REQUEST['_hint'], 'UTF-8,CP51932,CP932');
        if ($request_encoding == 'SJIS-win') {
            $request_encoding = false;
        }
    } else {
        $request_encoding = 'UTF-8,CP51932,CP932';
    }

    if (get_magic_quotes_gpc()) {
        $_GET = array_map('stripslashes_r', $_GET);
        $_POST = array_map('stripslashes_r', $_POST);
    }

    if ($request_encoding) {
        mb_convert_variables('CP932', $request_encoding, $_GET, $_POST);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $_POST = array_map('nullfilter_r', $_POST);
        if (count($_GET)) {
            $_GET = array_map('nullfilter_r', $_GET);
            $_REQUEST = array_merge($_GET, $_POST);
        } else {
            $_REQUEST = $_POST;
        }
    } else {
        $_GET = array_map('nullfilter_r', $_GET);
        $_REQUEST = $_GET;
    }
} else {
    $_REQUEST = array();
}

// }}}
// {{{ 管理者用設定etc.

// 管理者用設定を読み込み
if (!include_once './conf/conf_admin.inc.php') {
    p2die('管理者用設定ファイルを読み込めませんでした。');
}

// ディレクトリの絶対パス化
$_conf['data_dir'] = p2realpath($_conf['data_dir']);
$_conf['dat_dir']  = p2realpath($_conf['dat_dir']);
$_conf['idx_dir']  = p2realpath($_conf['idx_dir']);
$_conf['pref_dir'] = p2realpath($_conf['pref_dir']);

// 管理用保存ディレクトリ (パーミッションは707)
$_conf['admin_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'admin';

// cache 保存ディレクトリ (パーミッションは707)
// 2005/6/29 $_conf['pref_dir'] . '/p2_cache' より変更
$_conf['cache_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'cache';

// テンポラリディレクトリ (パーミッションは707)
$_conf['tmp_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'tmp';

// バージョンIDを二重引用符やヒアドキュメント内に埋め込むための変数
$_conf['p2_version_id'] = P2_VERSION_ID;

$_conf['doctype'] = '';
$_conf['accesskey'] = 'accesskey';
$_conf['meta_charset_ht'] = '<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">';

// 文字コード自動判定用のヒント文字列
$_conf['detect_hint'] = '◎◇';
$_conf['detect_hint_input_ht'] = '<input type="hidden" name="_hint" value="◎◇">';
$_conf['detect_hint_input_xht'] = '<input type="hidden" name="_hint" value="◎◇" />';
//$_conf['detect_hint_utf8'] = mb_convert_encoding('◎◇', 'UTF-8', 'CP932');
$_conf['detect_hint_q'] = '_hint=%81%9D%81%9E'; // rawurlencode($_conf['detect_hint'])
$_conf['detect_hint_q_utf8'] = '_hint=%E2%97%8E%E2%97%87'; // rawurlencode($_conf['detect_hint_utf8'])

// }}}
// {{{ 端末判定

$_conf['login_check_ip']  = 1; // ログイン時にIPアドレスを検証する
$_conf['input_type_search'] = false;
$_conf['extra_headers_ht'] = '';

$mobile = Net_UserAgent_Mobile::singleton();

// iPhone, iPod Touch
if (P2Util::isBrowserIphone()) {
    $_conf['ktai'] = true;
    $_conf['iphone'] = true;
    $_conf['disable_cookie'] = false;
    $_conf['accept_charset'] = 'UTF-8';
    $_conf['input_type_search'] = true;

// PC
} elseif ($mobile->isNonMobile()) {
    $_conf['ktai'] = false;
    $_conf['iphone'] = false;
    $_conf['disable_cookie'] = false;

    if (P2Util::isBrowserSafariGroup()) {
        $_conf['accept_charset'] = 'UTF-8';
        $_conf['input_type_search'] = true;
    } else {
        $_conf['accept_charset'] = 'Shift_JIS';
        if (P2Util::isClientOSWindowsCE() || P2Util::isBrowserNintendoDS() || P2Util::isBrowserPSP()) {
            $_conf['ktai'] = true;
        }
    }

// 携帯
} else {
    require_once P2_LIB_DIR . '/hostcheck.class.php';

    $_conf['ktai'] = true;
    $_conf['iphone'] = false;
    $_conf['accept_charset'] = 'Shift_JIS';

    // ベンダ判定
    // DoCoMo i-Mode
    if ($mobile->isDoCoMo()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrDocomo()) {
            p2die("UAがDoCoMoですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        $_conf['disable_cookie'] = true;
    // EZweb (au or Tu-Ka)
    } elseif ($mobile->isEZweb()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAu()) {
            p2die("UAがEZwebですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        $_conf['disable_cookie'] = false;
    // Vodafone Live!
    } elseif ($mobile->isVodafone()) {
        if ($_conf['login_check_ip'] && !HostCheck::isAddrSoftBank()) {
            p2die("UAがSoftBankですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        //$_conf['accesskey'] = 'DIRECTKEY';
        // W型端末と3GC型端末はCookieが使える
        if ($mobile->isTypeW() || $mobile->isType3GC()) {
            $_conf['disable_cookie'] = false;
        } else {
            $_conf['disable_cookie'] = true;
        }
    // AirH" Phone
    } elseif ($mobile->isAirHPhone()) {
        /*
        // AirH"では端末ID認証を行わないので、コメントアウト
        if ($_conf['login_check_ip'] && !HostCheck::isAddrAirh()) {
            p2die("UAがAirH\"ですが、IPアドレス帯域がマッチしません。({$_SERVER['REMOTE_ADDR']})");
        }
        */
        $_conf['disable_cookie'] = false;
    // その他
    } else {
        $_conf['disable_cookie'] = true;
    }
}

// }}}
// {{{ クエリーによる強制ビュー指定

// b=pc はまだリンク先が完全でない?
// b=i はCSSでWebKitの独自拡張/先行実装プロパティを多用している

$_conf['b'] = $_conf['client_type'] = ($_conf['iphone'] ? 'i' : ($_conf['ktai'] ? 'k' : 'pc'));
$_conf['view_forced_by_query'] = false;
$_conf['k_at_a'] = '';
$_conf['k_at_q'] = '';
$_conf['k_input_ht'] = '';

if (isset($_REQUEST['b'])) {
    switch ($_REQUEST['b']) {

    // 強制PCビュー指定
    case 'pc':
        if ($_conf['b'] != 'pc') {
            $_conf['b'] = 'pc';
            $_conf['ktai'] = false;
            $_conf['iphone'] = false;
        }
        break;

    // 強制iPhoneビュー指定
    case 'i':
        if ($_conf['b'] != 'i') {
            $_conf['b'] = 'i';
            $_conf['ktai'] = true;
            $_conf['iphone'] = true;
        }
        break;

    // 強制携帯ビュー指定
    case 'k':
        if ($_conf['b'] != 'k') {
            $_conf['b'] = 'k';
            $_conf['ktai'] = true;
            $_conf['iphone'] = false;
        }
        break;

    } // endswitch

    // 強制ビュー指定されていたなら
    if ($_conf['b'] != $_conf['client_type']) {
        $_conf['view_forced_by_query'] = true;
        $_conf['k_at_a'] = '&amp;b=' . $_conf['b'];
        $_conf['k_at_q'] = '?b=' . $_conf['b'];
        $_conf['k_input_ht'] = '<input type="hidden" name="b" value="' . $_conf['b'] . '">';
        //output_add_rewrite_var('b', $_conf['b']);
    }
}

// }}}
// {{{ 携帯・iPhone用変数

// iPhone用HTMLヘッダ要素
if ($_conf['client_type'] == 'i') {
    switch ($_conf['b']) {

    // 強制PCビュー時
    case 'pc':
        $_conf['extra_headers_ht'] = <<<EOS
<meta name="format-detection" content="telephone=no">
<link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png">
<style type="text/css">body { -webkit-text-size-adjust: none; }</style>
EOS;
        break;

    // 強制携帯ビュー時
    case 'k':
        $_conf['extra_headers_ht'] = <<<EOS
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes">
<meta name="format-detection" content="telephone=no">
<link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png">
<style type="text/css">
body { word-break: normal; word-break: break-all; -webkit-text-size-adjust: none; }
* { font-family: sans-serif; font-size: medium; line-height: 150%; }
h1 { font-size: xx-large; }
h2 { font-size: x-large; }
h3 { font-size: large; }
</style>
EOS;
        break;

    // 純正iPhoneビュー
    case 'i':
    default:
        $_conf['extra_headers_ht'] = <<<EOS
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes">
<meta name="format-detection" content="telephone=no">
<link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png">
<link rel="stylesheet" type="text/css" media="screen" href="css/iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/iphone.js?{$_conf['p2_version_id']}"></script>
EOS;

    } // endswitch

// 強制iPhoneビュー時
} elseif ($_conf['iphone']) {
    $_conf['extra_headers_ht'] = <<<EOS
<link rel="stylesheet" type="text/css" media="screen" href="css/iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
}

// 携帯用「トップに戻る」リンク
if ($_conf['ktai']) {
    $_conf['k_to_index_ht'] = <<<EOP
<a {$_conf['accesskey']}="0" href="index.php{$_conf['k_at_q']}">0.TOP</a>
EOP;
}

// }}}
// {{{ DOCTYPE HTML 宣言

$ie_strict = false;
if (!$_conf['ktai'] || $_conf['client_type'] != 'k') {
    if ($ie_strict || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false) {
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
// {{{ ユーザ設定 読込

// ユーザ設定ファイル
$_conf['conf_user_file'] = $_conf['pref_dir'] . '/conf_user.srd.cgi';

// 旧形式ファイルをコピー
$conf_user_file_old = $_conf['pref_dir'] . '/conf_user.inc.php';
if (!file_exists($_conf['conf_user_file']) && file_exists($conf_user_file_old)) {
    $old_cont = DataPhp::getDataPhpCont($conf_user_file_old);
    FileCtl::make_datafile($_conf['conf_user_file'], $_conf['conf_user_perm']);
    if (FileCtl::file_write_contents($_conf['conf_user_file'], $old_cont) === false) {
        $_info_msg_ht .= '<p>旧形式ユーザ設定のコピーに失敗しました。</p>';
    }
}

// ユーザ設定があれば読み込む
if (file_exists($_conf['conf_user_file'])) {
    if ($cont = file_get_contents($_conf['conf_user_file'])) {
        $conf_user = unserialize($cont);
    } else {
        $conf_user = null;
    }

    // 何らかの理由でユーザ設定ファイルが壊れていたら
    if (!is_array($conf_user)) {
        if (unlink($_conf['conf_user_file'])) {
            $_info_msg_ht .= '<p>ユーザ設定ファイルが壊れていたので破棄しました。</p>';
        } else {
            $_info_msg_ht .= '<p>ユーザ設定ファイルが壊れていますが、破棄できませんでした。<br>&quot;';
            $_info_msg_ht .= htmlspecialchars($_conf['conf_user_file'], ENT_QUOTES);
            $_info_msg_ht .= '&quot; を手動で削除してください。</p>';
        }
        $conf_user = array();
        $conf_user_mtime = 0;
    } else {
        $conf_user_mtime = filemtime($_conf['conf_user_file']);
    }

    // ユーザ設定ファイルとデフォルト設定ファイルの更新日時をチェック
    if (!isset($conf_user['.']) ||
        $conf_user['.'] != P2_VERSION_ID ||
        filemtime(__FILE__) > $conf_user_mtime ||
        filemtime('./conf/conf_user_def.inc.php')    > $conf_user_mtime ||
        filemtime('./conf/conf_user_def_ex.inc.php') > $conf_user_mtime ||
        filemtime('./conf/conf_user_def_i.inc.php')  > $conf_user_mtime)
    {
        // デフォルト設定を読み込む
        include_once './conf/conf_user_def.inc.php';
        $_conf = array_merge($_conf, $conf_user_def, $conf_user);

        // 新しいユーザ設定をキャッシュ
        $conf_user = array('.' => P2_VERSION_ID);
        foreach ($conf_user_def as $k => $v) {
            $conf_user[$k] = $_conf[$k];
        }
        if (FileCtl::file_write_contents($_conf['conf_user_file'], serialize($conf_user)) === false) {
            $_info_msg_ht .= '<p>ユーザ設定のキャッシュに失敗しました</p>';
        }

    // ユーザ設定ファイルの更新日時の方が新しい場合は、デフォルト設定を無視
    } else {
        $_conf = array_merge($_conf, $conf_user);
    }

    unset($cont, $conf_user);
} else {
    // デフォルト設定を読み込む
    include_once './conf/conf_user_def.inc.php';
    $_conf = array_merge($_conf, $conf_user_def);
}

// }}}
// {{{ デフォルト設定

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
$skin = './conf/conf_user_style.inc.php';
if (!$_conf['ktai'] && $_conf['expack.skin.enabled']) {
    if (file_exists($_conf['expack.skin.setting_path'])) {
        $skin_name = rtrim(file_get_contents($_conf['expack.skin.setting_path']));
        $skin = './skin/' . $skin_name . '.php';
    } else {
        FileCtl::make_datafile($_conf['expack.skin.setting_path'], $_conf['expack.skin.setting_perm']);
    }
    if (isset($_REQUEST['skin']) && preg_match('/^\w+$/', $_REQUEST['skin']) && $skin_name != $_REQUEST['skin']) {
        $skin_name = $_REQUEST['skin'];
        $skin = './skin/' . $skin_name . '.php';
        FileCtl::file_write_contents($_conf['expack.skin.setting_path'], $skin_name);
    }
}
if (!file_exists($skin)) {
    $skin_name = 'conf_user_style';
    $skin = './conf/conf_user_style.inc.php';
}
$skin_en = rawurlencode($skin_name) . '&amp;_=' . P2_VERSION_ID;
if ($_conf['view_forced_by_query']) {
    $skin_en .= $_conf['k_at_a'];
}
include_once $skin;

// }}}
// {{{ デザイン設定の調整処理

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

$skin_uniq = P2_VERSION_ID;
fontconfig_apply_custom();

foreach ($STYLE as $K => $V) {
    if (empty($V)) {
        $STYLE[$K] = '';
    } elseif (strpos($K, 'fontfamily') !== false) {
        $STYLE[$K] = set_css_fonts($V);
    } elseif (strpos($K, 'color') !== false) {
        $STYLE[$K] = set_css_color($V);
    } elseif (strpos($K, 'background') !== false) {
        $STYLE[$K] = 'url("' . addslashes($V) . '")';
    }
}
if (!$_conf['ktai'] && $_conf['expack.am.enabled']) {
    $_conf['expack.am.fontfamily'] = set_css_fonts($_conf['expack.am.fontfamily']);
    if ($STYLE['fontfamily']) {
        $_conf['expack.am.fontfamily'] .= '","' . $STYLE['fontfamily'];
    }
}

// }}}
// {{{ 携帯用カラーリングの調整処理

$_conf['k_colors'] = '';

if ($_conf['ktai']) {
    // 基本色
    if (!$_conf['iphone']) {
        if ($_conf['mobile.background_color']) {
            $_conf['k_colors'] .= ' bgcolor="' . htmlspecialchars($_conf['mobile.background_color']) . '"';
        }
        if ($_conf['mobile.text_color']) {
            $_conf['k_colors'] .= ' text="' . htmlspecialchars($_conf['mobile.text_color']) . '"';
        }
        if ($_conf['mobile.link_color']) {
            $_conf['k_colors'] .= ' link="' . htmlspecialchars($_conf['mobile.link_color']) . '"';
        }
        if ($_conf['mobile.vlink_color']) {
            $_conf['k_colors'] .= ' vlink="' . htmlspecialchars($_conf['mobile.vlink_color']) . '"';
        }
    }

    // 文字色
    if ($_conf['mobile.newthre_color']) {
        $STYLE['mobile_subject_newthre_color'] = htmlspecialchars($_conf['mobile.newthre_color']);
    }
    if ($_conf['mobile.newres_color']) {
        $STYLE['mobile_read_newres_color']    = htmlspecialchars($_conf['mobile.newres_color']);
        $STYLE['mobile_subject_newres_color'] = htmlspecialchars($_conf['mobile.newres_color']);
    }
    if ($_conf['mobile.ttitle_color']) {
        $STYLE['mobile_read_ttitle_color'] = htmlspecialchars($_conf['mobile.ttitle_color']);
    }
    if ($_conf['mobile.ngword_color']) {
        $STYLE['mobile_read_ngword_color'] = htmlspecialchars($_conf['mobile.ngword_color']);
    }
    if ($_conf['mobile.onthefly_color']) {
        $STYLE['mobile_read_onthefly_color'] = htmlspecialchars($_conf['mobile.onthefly_color']);
    }

    // マーカー
    if ($_conf['mobile.match_color']) {
        if ($_conf['iphone']) {
            $_conf['extra_headers_ht'] .= sprintf('<style type="text/css">b.filtering, span.matched { color: %s; }</style>',
                                                  htmlspecialchars($_conf['mobile.match_color']));
            $_conf['k_filter_marker'] = '<span class="matched">\\1</span>';
        } else {
            $_conf['k_filter_marker'] = '<font color="' . htmlspecialchars($_conf['mobile.match_color']) . '">\\1</font>';
        }
    } else {
        $_conf['k_filter_marker'] = false;
    }
}

// }}}
// {{{ 変数設定

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

$_conf['matome_cache_path'] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR . 'matome_cache';
$_conf['matome_cache_ext'] = '.htm';
$_conf['matome_cache_max'] = 3; // 予備キャッシュの数

$_conf['orig_favlist_file'] = $_conf['favlist_file'];
$_conf['orig_favita_path']  = $_conf['favita_path'];

// }}}
// {{{ ホストチェック

if ($_conf['secure']['auth_host'] || $_conf['secure']['auth_bbq']) {
    require_once P2_LIB_DIR . '/hostcheck.class.php';
    if (($_conf['secure']['auth_host'] && HostCheck::getHostAuth() == false) ||
        ($_conf['secure']['auth_bbq'] && HostCheck::getHostBurned() == true)
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
    $_conf['session_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'session';
}

if (defined('P2_FORCE_USE_SESSION') || $_conf['expack.misc.multi_favs']) {
    $_conf['use_session'] = 1;
}

if ($_conf['use_session'] == 1 or ($_conf['use_session'] == 2 && !$_COOKIE['cid'])) {

    // {{{ セッションデータ保存ディレクトリをチェック

    if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
        if (!is_dir($_conf['session_dir'])) {
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

    $_p2session = new Session();
    if ($_conf['disable_cookie'] && !ini_get('session.use_trans_sid')) {
        output_add_rewrite_var(session_name(), session_id());
    }
}

// }}}
// {{{ お気にセット

// 複数のお気にセットを使うとき
if ($_conf['expack.misc.multi_favs']) {
    require_once P2_LIB_DIR . '/favsetmng.class.php';
    // 切り替え表示用に全てのお気に板を読み込んでおく
    FavSetManager::loadAllFavSet();
    // お気にセットを切り替える
    FavSetManager::switchFavSet();
} else {
    $_conf['m_favlist_set'] = $_conf['m_favlist_set_at_a'] = $_conf['m_favlist_set_input_ht'] = '';
    $_conf['m_favita_set']  = $_conf['m_favita_set_at_a']  = $_conf['m_favita_set_input_ht']  = '';
    $_conf['m_rss_set']     = $_conf['m_rss_set_at_a']     = $_conf['m_rss_set_input_ht']     = '';
}

// }}}
// {{{ misc.

// XHTMLヘッダ要素
if (defined('P2_OUTPUT_XHTML')) {
    $_conf['extra_headers_xht'] = preg_replace('/<((?:link|meta) .+?)>/', '<\\1 />', $_conf['extra_headers_ht']);
}

// ログインクラスのインスタンス生成（ログインユーザが指定されていなければ、この時点でログインフォーム表示に）
require_once P2_LIB_DIR . '/login.class.php';
$_login = new Login();

// おまじない
$a = ceil(1/2);
$b = floor(1/3);
$c = round(1/4, 1);

// }}}
}

// }}} p2configure()
// {{{ stripslashes_r()

/**
 * 再帰的にstripslashesをかける
 * GET/POST/COOKIE変数用なのでオブジェクトのプロパティには対応しない
 * (ExUtil)
 *
 * @return  array|string
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

// }}}
// {{{ addslashes_r()

/**
 * 再帰的にaddslashesをかける
 * (ExUtil)
 *
 * @return  array|string
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

// }}}
// {{{ nullfilter_r()

/**
 * 再帰的にヌル文字を削除する
 *
 * NULLバイトアタック対策
 *
 * @return  array|string
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

// }}}
// {{{ printMemoryUsage()

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

// }}}
// {{{ si2int(), si2real()

/**
 * SI単位系の値を整数に変換する
 * 厳密には1000倍するのが正しいが、PC界隈 (記憶装置除く) の慣例に従って1024倍する
 *
 * @return float
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

// }}}
// {{{ mb_basename()

/**
 * マルチバイト対応のbasename()
 *
 * @return string
 */
function mb_basename($path, $encoding = 'CP932')
{
    if (!mb_substr_count($path, '/', $encoding)) {
        return $path;
    }
    $len = mb_strlen($path, $encoding);
    $pos = mb_strrpos($path, '/', $encoding);
    return mb_substr($path, $pos + 1, $len - $pos, $encoding);
}

// }}}
// {{{ fontconfig_detect_agent()

/**
 * フォント設定用にユーザエージェントを判定する
 *
 * @return string
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
        if (preg_match('/\b(Safari|AppleWebKit)\/([\d]+)/', $ua, $matches)) {
            $version = (int)$matches[2];
            if ($version >= 500) {
                return 'safari3';
            } else if ($version >= 400) {
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

// }}}
// {{{ fontconfig_apply_custom()

/**
 * フォント設定を読み込む
 *
 * @return void
 */
function fontconfig_apply_custom()
{
    global $STYLE, $_conf, $skin_en, $skin_uniq;
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
            $skin_uniq = P2_VERSION_ID . sprintf('.%u', crc32($fontconfig_data));
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
    $skin_en = preg_replace('/&amp;_=[^&]*/', '', $skin_en) . '&amp;_=' . rawurlencode($skin_uniq);
}

// }}}
// {{{ print_style_tags()

/**
 * スタイルシートを読み込むタグを表示
 *
 * @return void
 */
function print_style_tags()
{
    global $skin_name, $skin_uniq;
    $style_a = '';
    if (strlen($skin_name)) { $style_a .= '&skin=' . rawurlencode($skin_name); }
    if (strlen($skin_uniq)) { $style_a .= '&_=' . rawurlencode($skin_uniq); }
    if ($styles = func_get_args()) {
        echo "\t<style type=\"text/css\">\n";
        foreach ($styles as $style) {
            if (file_exists(P2_STYLE_DIR . '/' . $style . '_css.inc')) {
                printf("\t@import 'css.php?css=%s%s';\n", $style, $style_a);
            }
        }
        echo "\t</style>\n";
    }
}

// }}}
// {{{ set_css_fonts()

/**
 * スタイルシートのフォント指定を調整する
 *
 * @return string
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

// }}}
// {{{ set_css_color()

/**
 * スタイルシートの色指定を調整する
 *
 * @return string
 */
function set_css_color($color)
{
    return preg_replace('/^#([0-9A-F])([0-9A-F])([0-9A-F])$/i', '#$1$1$2$2$3$3', $color);
}

// }}}
// {{{ combine_nfd_kana()

/**
 * Safari からアップロードされたファイル名の文字化けを補正する関数
 * 清音+濁点・清音+半濁点を一文字にまとめる (NFD で正規化された かな を NFC にする)
 * 入出力の文字コードはUTF-8
 *
 * @return string
 */
function combine_nfd_kana($str)
{
    /*
    static $regex = null;
    if ($regex === null) {
        // UTF-8,NFDの濁音・半濁音にマッチする正規表現
        $regex = str_replace(array('%u3099%', '%u309A%'),
                             array(pack('C*', 0xE3, 0x82, 0x99), pack('C*', 0xE3, 0x82, 0x9A)),
                             mb_convert_encoding('/([うか-こさ-そた-とは-ほウカ-コサ-ソタ-トハ-ホゝヽ])%u3099%'
                                                 . '|([は-ほハ-ホ])%u309A%/u',
                                                 'UTF-8',
                                                 'CP932'
                                                 )
                             );
    }
    return preg_replace_callback($regex, '_combine_nfd_kana', $str);
    */
    return preg_replace_callback('/([\\x{3046}\\x{304b}-\\x{3053}\\x{3055}-\\x{305d}\\x{305f}-\\x{3068}\\x{306f}-\\x{307b}\\x{30a6}\\x{30ab}-\\x{30b3}\\x{30b5}-\\x{30bd}\\x{30bf}-\\x{30c8}\\x{30cf}-\\x{30db}\\x{309d}\\x{30fd}])\\x{3099}|([\\x{306f}-\\x{307b}\\x{30cf}-\\x{30db}])\\x{309a}/u', '_combine_nfd_kana', $str);
}

function _combine_nfd_kana($m)
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

// }}}
// {{{ wakati()

// 漢字にマッチする正規表現
//$GLOBALS['KANJI_REGEX'] = mb_convert_encoding('/[一-龠]/u', 'UTF-8', 'CP932');
$GLOBALS['KANJI_REGEX'] = '/[\\x{4e00}-\\x{9fa0}]/u';

// すごく適当な分かち書き用正規表現
/*
$GLOBALS['WAKATI_REGEX'] = mb_convert_encoding('/(' . implode('|', array(
    //'[一-龠]+[ぁ-ん]*',
    //'[一-龠]+',
    '[一二三四五六七八九十]+',
    '[丁-龠]+',
    '[ぁ-ん][ぁ-んー～゛゜]*',
    '[ァ-ヶ][ァ-ヶー～゛゜]*',
    //'[a-z][a-z_\\-]*',
    //'[0-9][0-9.]*',
    '[0-9a-z][0-9a-z_\\-]*',
)) . ')/u', 'UTF-8', 'CP932');
*/
$GLOBALS['WAKATI_REGEX'] = <<<EOP
/(
#[\\x{4e00}-\\x{9fa0}]+[\\x{3041}-\\x{3093}]*|
#[\\x{4e00}-\\x{9fa0}]+|
[\\x{4e00}\\x{4e8c}\\x{4e09}\\x{56db}\\x{4e94}\\x{516d}\\x{4e03}\\x{516b}\\x{4e5d}\\x{5341}]+|
[\\x{4e01}-\\x{9fa0}]+|
[\\x{3041}-\\x{3093}][\\x{3041}-\\x{3093}\\x{30fc}\\x{301c}\\x{309b}\\x{309c}]*|
[\\x{30a1}-\\x{30f6}][\\x{30a1}-\\x{30f6}\\x{30fc}\\x{301c}\\x{309b}\\x{309c}]*|
#[a-z][a-z_\\-]*|
#[0-9][0-9.]*|
[0-9a-z][0-9a-z_\\-]*)/ux
EOP;

/**
 * すごく適当な正規化＆分かち書き関数
 *
 * @return array
 */
function wakati($str)
{
    return array_filter(array_map('trim', preg_split($GLOBALS['WAKATI_REGEX'],
        mb_strtolower(mb_convert_kana(mb_convert_encoding(
            $str, 'UTF-8', 'CP932'), 'KVas', 'UTF-8'), 'UTF-8'),
        -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)), 'strlen');
}

// }}}
// {{{ p2die()

/**
 * メッセージを表示して終了
 *
 * @param   string  $err    エラー概要
 * @param   string  $msg    詳細な説明
 * @param   boolean $raw    詳細な説明をエスケープするか否か
 * @return  void
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

// }}}
// {{{ p2checkenv()

/**
 * 動作環境を確認する
 *
 * @return  void
 */
function p2checkenv($check_recommended)
{
    global $_info_msg_ht;

    $php_version = phpversion();
    $required_version = '5.2.3';
    $recommended_version = '5.2.6';

    if (version_compare($php_version, $required_version, '<')) {
        p2die('PHP ' . $required_version . ' 未満では使えません。');
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

    if ($check_recommended && version_compare($php_version, $recommended_version, '<')) {
        $_info_msg_ht .= '<p><b>推奨バージョンより古いPHPで動作しています。</b> <i>(PHP ' . $php_version . ')</i><br>';
        $_info_msg_ht .= 'PHP ' . $recommended_version . ' 以降にアップデートすることをおすすめします。<br>';
        $_info_msg_ht .= '<small>（このメッセージを表示しないようにするには ' . htmlspecialchars(__FILE__, ENT_QUOTES);
        $_info_msg_ht .= ' の ' . $check_recommended . ' 行目の &quot;p2checkenv(__LINE__);&quot; を';
        $_info_msg_ht .= ' &quot;p2checkenv(false);&quot; に書き換えてください）</small></p>';
    }
}

// }}}
// {{{ p2realpath()

/**
 * 実在しない(かもしれない)ファイルの絶対パスを取得する
 */
function p2realpath($path)
{
    if (file_exists($path)) {
        return realpath($path);
    }
    if (!class_exists('File_Util', false)) {
        require_once 'File/Util.php';
    }
    return File_Util::realPath($path);
}

// }}}
// {{{ __autoload()

/**
 * PEARで第2引数をfalseにせずにclass_exists()を読んでいる可能性があるので
 * __autoload()を使うのは怖い
 */
/*function __autoload($class_name)
{
    require_once str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
}*/

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
