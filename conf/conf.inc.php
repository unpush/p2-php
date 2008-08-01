<?php
/**
 * rep2 - 基本設定ファイル
 * このファイルは、特に理由の無い限り変更しないこと
 */

// バージョン情報
$_conf = array(
    'p2version' => '1.7.29+1.8.x',  // rep2のバージョン
    'p2expack'  => '090710.2130',   // 拡張パックのバージョン
    'p2name'    => 'expack',        // rep2の名前
);

$_conf['p2ua'] = "{$_conf['p2name']}/{$_conf['p2version']}+{$_conf['p2expack']}";

define('P2_VERSION_ID', sprintf('%u', crc32($_conf['p2ua'])));

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
if (defined('E_DEPRECATED')) {
    error_reporting(E_ALL & ~(E_NOTICE | E_STRICT | E_DEPRECATED));
} else {
    error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));
}

// {{{ 基本変数

$_conf['p2web_url']             = 'http://akid.s17.xrea.com/';
$_conf['p2ime_url']             = 'http://akid.s17.xrea.com/p2ime.php';
$_conf['favrank_url']           = 'http://akid.s17.xrea.com/favrank/favrank.php';
$_conf['expack.web_url']        = 'http://page2.xrea.jp/expack/';
$_conf['expack.download_url']   = 'http://page2.xrea.jp/expack/index.php/download';
$_conf['expack.history_url']    = 'http://page2.xrea.jp/expack/index.php/history';
$_conf['expack.tgrep_url']      = 'http://page2.xrea.jp/tgrep/search';
$_conf['expack.ime_url']        = 'http://page2.xrea.jp/r.p';
$_conf['menu_php']              = 'menu.php';
$_conf['subject_php']           = 'subject.php';
$_conf['read_php']              = 'read.php';
$_conf['read_new_php']          = 'read_new.php';
$_conf['read_new_k_php']        = 'read_new_k.php';

// }}}
// {{{ 環境設定

// デバッグ
//$debug = !empty($_GET['debug']);

putenv('LC_CTYPE=C');

// タイムゾーンをセット
date_default_timezone_set('Asia/Tokyo');

// スクリプト実行制限時間 (秒)
if (!defined('P2_CLI_RUN')) {
    set_time_limit(60); // (60) 
}

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

// mbstring.script_encoding = SJIS-win だと "\0", "\x00" 以降がカットされるので
define('P2_NULLBYTE', chr(0));

// }}}
// {{{ P2Util::header_content_type() を不要にするおまじない

ini_set('default_mimetype', 'text/html');
ini_set('default_charset', 'Shift_JIS');

// }}}
// {{{ ライブラリ類のパス設定

define('P2_CONF_DIR', dirname(__FILE__)); // __DIR__ @php-5.3

define('P2_BASE_DIR', dirname(P2_CONF_DIR));

// 基本的な機能を提供するするライブラリ
define('P2_LIB_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib');

// おまけ的な機能を提供するするライブラリ
define('P2EX_LIB_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'expack');

// スタイルシート
define('P2_STYLE_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'style');

// スキン
define('P2_SKIN_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'skin');

// PEARインストールディレクトリ、検索パスに追加される
define('P2_PEAR_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'includes');

// PEARをハックしたファイル用ディレクトリ、通常のPEARより優先的に検索パスに追加される
// Cache/Container/db.php(PEAR::Cache)がMySQL縛りだったので、汎用的にしたものを置いている
// include_pathを追加するのはパフォーマンスに影響を及ぼすため、本当に必要な場合のみ定義
if (defined('P2_USE_PEAR_HACK')) {
    define('P2_PEAR_HACK_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pear_hack');
}

// コマンドラインツール
define('P2_CLI_DIR', P2_BASE_DIR . DIRECTORY_SEPARATOR . 'cli');

// 検索パスをセット
$include_path = '';
if (defined('P2_PEAR_HACK_DIR')) {
    $include_path .= P2_PEAR_HACK_DIR . PATH_SEPARATOR;
}
if (is_dir(P2_PEAR_DIR)) {
    $include_path .= P2_PEAR_DIR . PATH_SEPARATOR;
} else {
    $paths = array();
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir) {
        if (is_dir($dir)) {
            $dir = realpath($dir);
            if ($dir != P2_BASE_DIR) {
                $paths[] = $dir;
            }
        }
    }
    if (count($paths)) {
        $include_path .= implode(PATH_SEPARATOR, array_unique($paths)) . PATH_SEPARATOR;
    }
}
$include_path .= P2_BASE_DIR; // fallback
set_include_path($include_path);

$P2_CONF_DIR_S = P2_CONF_DIR . DIRECTORY_SEPARATOR;
$P2_LIB_DIR_S = P2_LIB_DIR . DIRECTORY_SEPARATOR;

// }}}
// {{{ 環境チェックとデバッグ

// ユーティリティを読み込む
require_once $P2_LIB_DIR_S . 'DataPhp.php';
require_once $P2_LIB_DIR_S . 'FileCtl.php';
require_once $P2_LIB_DIR_S . 'P2Util.php';
require_once $P2_LIB_DIR_S . 'p2util.inc.php';

// 動作環境を確認 (要件を満たしているならコメントアウト可)
p2checkenv(__LINE__);

if ($debug) {
    if (!class_exists('Benchmark_Profiler', false)) {
        require 'Benchmark/Profiler.php';
    }
    $profiler = new Benchmark_Profiler(true);
    // print_memory_usage();
    register_shutdown_function('print_memory_usage');
}

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
// {{{ 管理者用設定etc.

// 管理者用設定を読み込み
require_once $P2_CONF_DIR_S . 'conf_admin.inc.php';

// ディレクトリの絶対パス化
$_conf['data_dir'] = p2_realpath($_conf['data_dir']);
$_conf['dat_dir']  = p2_realpath($_conf['dat_dir']);
$_conf['idx_dir']  = p2_realpath($_conf['idx_dir']);
$_conf['pref_dir'] = p2_realpath($_conf['pref_dir']);

// 管理用保存ディレクトリ
$_conf['admin_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'admin';

// cache 保存ディレクトリ
// 2005/06/29 $_conf['pref_dir'] . '/p2_cache' より変更
$_conf['cache_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'cache';

// Cookie 保存ディレクトリ
// 2008/09/09 $_conf['pref_dir'] . '/p2_cookie' より変更
$_conf['cookie_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'cookie';

// コンパイルされたテンプレートの保存ディレクトリ
$_conf['compile_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'compile';

// セッションデータ保存ディレクトリ
$_conf['session_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'session';

// テンポラリディレクトリ
$_conf['tmp_dir'] = $_conf['data_dir'] . DIRECTORY_SEPARATOR . 'tmp';

// バージョンIDを二重引用符やヒアドキュメント内に埋め込むための変数
$_conf['p2_version_id'] = P2_VERSION_ID;

// 文字コード自動判定用のヒント文字列
$_conf['detect_hint'] = '◎◇';
$_conf['detect_hint_input_ht'] = '<input type="hidden" name="_hint" value="◎◇">';
$_conf['detect_hint_input_xht'] = '<input type="hidden" name="_hint" value="◎◇" />';
//$_conf['detect_hint_utf8'] = mb_convert_encoding('◎◇', 'UTF-8', 'CP932');
$_conf['detect_hint_q'] = '_hint=%81%9D%81%9E'; // rawurlencode($_conf['detect_hint'])
$_conf['detect_hint_q_utf8'] = '_hint=%E2%97%8E%E2%97%87'; // rawurlencode($_conf['detect_hint_utf8'])

// }}}
// {{{ 変数設定

$pref_dir_s = $_conf['pref_dir'] . DIRECTORY_SEPARATOR;

$_conf['favita_brd']        = $pref_dir_s . 'p2_favita.brd';        // お気に板 (brd)
$_conf['favlist_idx']       = $pref_dir_s . 'p2_favlist.idx';       // お気にスレ (idx)
$_conf['recent_idx']        = $pref_dir_s . 'p2_recent.idx';        // 最近読んだスレ (idx)
$_conf['palace_idx']        = $pref_dir_s . 'p2_palace.idx';        // スレの殿堂 (idx)
$_conf['res_hist_idx']      = $pref_dir_s . 'p2_res_hist.idx';      // 書き込みログ (idx)
$_conf['res_hist_dat']      = $pref_dir_s . 'p2_res_hist.dat';      // 書き込みログファイル (dat)
$_conf['res_hist_dat_php']  = $pref_dir_s . 'p2_res_hist.dat.php';  // 書き込みログファイル (データPHP)
$_conf['idpw2ch_php']       = $pref_dir_s . 'p2_idpw2ch.php';       // 2ch ID認証設定ファイル (データPHP)
$_conf['sid2ch_php']        = $pref_dir_s . 'p2_sid2ch.php';        // 2ch ID認証セッションID記録ファイル (データPHP)
$_conf['auth_user_file']    = $pref_dir_s . 'p2_auth_user.php';     // 認証ユーザ設定ファイル(データPHP)
$_conf['auth_imodeid_file'] = $pref_dir_s . 'p2_auth_imodeid.php';  // docomo iモードID認証ファイル (データPHP)
$_conf['auth_docomo_file']  = $pref_dir_s . 'p2_auth_docomo.php';   // docomo 端末製造番号認証ファイル (データPHP)
$_conf['auth_ez_file']      = $pref_dir_s . 'p2_auth_ez.php';       // EZweb サブスクライバID認証ファイル (データPHP)
$_conf['auth_jp_file']      = $pref_dir_s . 'p2_auth_jp.php';       // SoftBank 端末シリアル番号認証ファイル (データPHP)
$_conf['login_log_file']    = $pref_dir_s . 'p2_login.log.php';     // ログイン履歴 (データPHP)
$_conf['login_failed_log_file'] = $pref_dir_s . 'p2_login_failed.dat.php';  // ログイン失敗履歴 (データPHP)

$_conf['matome_cache_path'] = $pref_dir_s . 'matome_cache';
$_conf['matome_cache_ext']  = '.htm';
$_conf['matome_cache_max']  = 3; // 予備キャッシュの数

$_conf['orig_favita_brd']   = $_conf['favita_brd'];
$_conf['orig_favlist_idx']  = $_conf['favlist_idx'];

$_conf['cookie_file_name']  = 'p2_cookie.txt';

// 補正
if ($_conf['expack.use_pecl_http'] && !extension_loaded('http')) {
    if (!($_conf['expack.use_pecl_http'] == 2 && $_conf['expack.dl_pecl_http'])) {
        $_conf['expack.use_pecl_http'] = 0;
    }
}

// コマンドラインモードではここまで
if (defined('P2_CLI_RUN')) {
    return;
}

// }}}
// {{{ ホストチェック

if ($_conf['secure']['auth_host'] || $_conf['secure']['auth_bbq']) {
    require_once $P2_LIB_DIR_S . 'HostCheck.php';
    if (($_conf['secure']['auth_host'] && HostCheck::getHostAuth() == false) ||
        ($_conf['secure']['auth_bbq'] && HostCheck::getHostBurned() == true)
    ) {
        HostCheck::forbidden();
    }
}

// }}}
// {{{ リクエスト変数の処理

// 新規ログインとメンバーログインの同時指定はありえないので、エラーを出す
if (isset($_POST['submit_new']) && isset($_POST['submit_member'])) {
    p2die('無効なURLです。');
}

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
// {{{ 端末判定

require_once 'Net/UserAgent/Mobile.php';

$_conf['ktai'] = false;
$_conf['iphone'] = false;
$_conf['input_type_search'] = false;

$_conf['accesskey'] = 'accesskey';
$_conf['accept_charset'] = 'Shift_JIS';
$_conf['extra_headers_ht'] = '';

$support_cookies = true;

$mobile = Net_UserAgent_Mobile::singleton();

// iPhone, iPod Touch
if (P2Util::isBrowserIphone()) {
    $_conf['ktai'] = true;
    $_conf['iphone'] = true;
    $_conf['input_type_search'] = true;
    $_conf['accept_charset'] = 'UTF-8';

// PC等
} elseif ($mobile->isNonMobile()) {
    // Safari
    if (P2Util::isBrowserSafariGroup()) {
        $_conf['input_type_search'] = true;
        $_conf['accept_charset'] = 'UTF-8';

    // Windows Mobile, 携帯ゲーム機
    } elseif (P2Util::isClientOSWindowsCE() || P2Util::isBrowserNintendoDS() || P2Util::isBrowserPSP()) {
        $_conf['ktai'] = true;
    }

// 携帯
} else {
    $_conf['ktai'] = true;

    // NTT docomo iモード
    if ($mobile->isDoCoMo()) {
        $support_cookies = false;

    // au EZweb
    //} elseif ($mobile->isEZweb()) {
    //    $support_cookies = true;

    // SoftBank Mobile
    } elseif ($mobile->isSoftBank()) {
        // 3GC型端末はnonumber属性をサポートしないのでaccesskeyを使う
        if (!$mobile->isType3GC()) {
            $_conf['accesskey'] = 'DIRECTKEY';
            // 3GC型端末とW型端末以外はCookieをサポートしない
            if (!$mobile->isTypeW()) {
                $support_cookies = false;
            }
        }

    // WILLCOM AIR-EDGE
    //} elseif ($mobile->isWillcom()) {
    //    $support_cookies = true;

    // その他
    //} else {
    //    $support_cookies = true;
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
        filemtime($P2_CONF_DIR_S . 'conf_user_def.inc.php')    > $conf_user_mtime ||
        filemtime($P2_CONF_DIR_S . 'conf_user_def_ex.inc.php') > $conf_user_mtime ||
        filemtime($P2_CONF_DIR_S . 'conf_user_def_i.inc.php')  > $conf_user_mtime)
    {
        // デフォルト設定を読み込む
        require_once $P2_CONF_DIR_S . 'conf_user_def.inc.php';

        // 設定の更新
        if (!array_key_exists('mobile.link_youtube', $conf_user)) {
            require_once $P2_LIB_DIR_S . 'conf_user_updater.inc.php';
            $conf_user = conf_user_update_080908($conf_user);
        }

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
    require_once $P2_CONF_DIR_S . 'conf_user_def.inc.php';
    $_conf = array_merge($_conf, $conf_user_def);
}

// }}}
// {{{ ユーザ設定の調整処理

$_conf['ext_win_target_at'] = ($_conf['ext_win_target']) ? " target=\"{$_conf['ext_win_target']}\"" : '';
$_conf['bbs_win_target_at'] = ($_conf['bbs_win_target']) ? " target=\"{$_conf['bbs_win_target']}\"" : '';

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
$skin = $P2_CONF_DIR_S . 'conf_user_style.inc.php';
if (!$_conf['ktai'] && $_conf['expack.skin.enabled']) {
    if (file_exists($_conf['expack.skin.setting_path'])) {
        $skin_name = rtrim(file_get_contents($_conf['expack.skin.setting_path']));
        $skin = P2_SKIN_DIR . DIRECTORY_SEPARATOR . $skin_name . '.php';
    } else {
        FileCtl::make_datafile($_conf['expack.skin.setting_path'], $_conf['expack.skin.setting_perm']);
    }
    if (isset($_REQUEST['skin']) && preg_match('/^\\w+$/', $_REQUEST['skin']) && $skin_name != $_REQUEST['skin']) {
        $skin_name = $_REQUEST['skin'];
        $skin = P2_SKIN_DIR . DIRECTORY_SEPARATOR . $skin_name . '.php';
        FileCtl::file_write_contents($_conf['expack.skin.setting_path'], $skin_name);
    }
}
if (!file_exists($skin)) {
    $skin_name = 'conf_user_style';
    $skin = $P2_CONF_DIR_S . 'conf_user_style.inc.php';
}
$skin_en = rawurlencode($skin_name) . '&amp;_=' . P2_VERSION_ID;
if ($_conf['view_forced_by_query']) {
    $skin_en .= $_conf['k_at_a'];
}

// デフォルト設定を読み込んで
include $P2_CONF_DIR_S . 'conf_user_style.inc.php';
// スキンで上書き
if ($skin != $P2_CONF_DIR_S . 'conf_user_style.inc.php') {
    include $skin;
}

// }}}
// {{{ デザイン設定の調整処理

$skin_uniq = P2_VERSION_ID;

foreach ($STYLE as $K => $V) {
    if (empty($V)) {
        $STYLE[$K] = '';
    } elseif (strpos($K, 'fontfamily') !== false) {
        $STYLE[$K] = p2_correct_css_fontfamily($V);
    } elseif (strpos($K, 'color') !== false) {
        $STYLE[$K] = p2_correct_css_color($V);
    } elseif (strpos($K, 'background') !== false) {
        $STYLE[$K] = "url('" . p2_escape_css_url($V) . "')";
    }
}

if (!$_conf['ktai']) {
    require_once $P2_LIB_DIR_S . 'fontconfig.inc.php';

    if ($_conf['expack.am.enabled']) {
        $_conf['expack.am.fontfamily'] = p2_correct_css_fontfamily($_conf['expack.am.fontfamily']);
        if ($STYLE['fontfamily']) {
            $_conf['expack.am.fontfamily'] .= '","' . $STYLE['fontfamily'];
        }
    }

    fontconfig_apply_custom();
}

// }}}
// {{{ 携帯・iPhone用変数

// iPhone用HTMLヘッダ要素
if ($_conf['client_type'] == 'i') {
    switch ($_conf['b']) {

    // 強制PCビュー時
    case 'pc':
        $_conf['extra_headers_ht'] .= <<<EOS
<meta name="format-detection" content="telephone=no">
<link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png">
<style type="text/css">body { -webkit-text-size-adjust: none; }</style>
EOS;
        break;

    // 強制携帯ビュー時
    case 'k':
        $_conf['extra_headers_ht'] .= <<<EOS
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
        $_conf['extra_headers_ht'] .= <<<EOS
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes">
<meta name="format-detection" content="telephone=no">
<link rel="apple-touch-icon" type="image/png" href="img/touch-icon/p2-serif.png">
<link rel="stylesheet" type="text/css" media="screen" href="css/iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/iphone.js?{$_conf['p2_version_id']}"></script>
EOS;

    } // endswitch

// 強制iPhoneビュー時
} elseif ($_conf['iphone']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" media="screen" href="css/iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
}

// iPhone用スキン
if ($_conf['iphone'] && isset($_conf['expack.iphone.skin'])) {
    if (strpos($_conf['expack.iphone.skin'], DIRECTORY_SEPARATOR) === false) {
        $iskin = 'skin/iphone/' . $iskin . '.css';
        if (file_exists($iskin)) {
            $iskin_mtime = filemtime($iskin);
            $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" media="screen" href="{$iskin}?{$iskin_mtime}">
EOS;
        }
    }
}

// 携帯用「トップに戻る」リンクとaccesskey
if ($_conf['ktai']) {
    // iPhone
    if ($_conf['iphone']) {
        $_conf['k_accesskey_at'] = array_fill(0, 10, '');
        $_conf['k_accesskey_at']['*'] = '';
        $_conf['k_accesskey_at']['#'] = '';
        foreach ($_conf['k_accesskey'] as $name => $key) {
            $_conf['k_accesskey_at'][$name] = '';
        }

        $_conf['k_accesskey_st'] = $_conf['k_accesskey_at'];

        $_conf['k_to_index_ht'] = <<<EOP
<a href="index.php{$_conf['k_at_q']}" class="button">TOP</a>
EOP;

    // その他
    } else {
        // SoftBank Mobile
        if ($_conf['accesskey'] == 'DIRECTKEY') {
            $_conf['k_accesskey_at'] = array(
                '0' => ' directkey="0" nonumber',
                '1' => ' directkey="1" nonumber',
                '2' => ' directkey="2" nonumber',
                '3' => ' directkey="3" nonumber',
                '4' => ' directkey="4" nonumber',
                '5' => ' directkey="5" nonumber',
                '6' => ' directkey="6" nonumber',
                '7' => ' directkey="7" nonumber',
                '8' => ' directkey="8" nonumber',
                '9' => ' directkey="9" nonumber',
                '*' => ' directkey="*" nonumber',
                '#' => ' directkey="#" nonumber',
            );

        // その他
        } else {
            $_conf['k_accesskey_at'] = array(
                '0' => ' accesskey="0"',
                '1' => ' accesskey="1"',
                '2' => ' accesskey="2"',
                '3' => ' accesskey="3"',
                '4' => ' accesskey="4"',
                '5' => ' accesskey="5"',
                '6' => ' accesskey="6"',
                '7' => ' accesskey="7"',
                '8' => ' accesskey="8"',
                '9' => ' accesskey="9"',
                '*' => ' accesskey="*"',
                '#' => ' accesskey="#"',
            );
        }

        switch ($_conf['mobile.display_accesskey']) {
        case 2:
            require_once $P2_LIB_DIR_S . 'emoji.inc.php';
            $emoji = p2_get_emoji($mobile);
            //$emoji = p2_get_emoji(Net_UserAgent_Mobile::factory('KDDI-SA31 UP.Browser/6.2.0.7.3.129 (GUI) MMP/2.0'));
            $_conf['k_accesskey_st'] = array(
                '0' => $emoji[0],
                '1' => $emoji[1],
                '2' => $emoji[2],
                '3' => $emoji[3],
                '4' => $emoji[4],
                '5' => $emoji[5],
                '6' => $emoji[6],
                '7' => $emoji[7],
                '8' => $emoji[8],
                '9' => $emoji[9],
                '*' => $emoji['*'],
                '#' => $emoji['#'],
            );
            break;
        case 0:
            $_conf['k_accesskey_st'] = array_fill(0, 10, '');
            $_conf['k_accesskey_st']['*'] = '';
            $_conf['k_accesskey_st']['#'] = '';
            break;
        case 1:
        default:
            $_conf['k_accesskey_st'] = array(
                0 => '0.', 1 => '1.', 2 => '2.', 3 => '3.', 4 => '4.',
                5 => '5.', 6 => '6.', 7 => '7.', 8 => '8.', 9 => '9.',
                '*' => '*.', '#' => '#.'
            );
        }

        foreach ($_conf['k_accesskey'] as $name => $key) {
            $_conf['k_accesskey_at'][$name] = $_conf['k_accesskey_at'][$key];
            $_conf['k_accesskey_st'][$name] = $_conf['k_accesskey_st'][$key];
        }

        $_conf['k_to_index_ht'] = <<<EOP
<a href="index.php{$_conf['k_at_q']}"{$_conf['k_accesskey_at'][0]}>{$_conf['k_accesskey_st'][0]}TOP</a>
EOP;
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
// {{{ セッション

// クッキーが使えない場合にsession.use_only_cookiesが1だとセッションが
// 継続できないので（セキュリティリスクが高まるがクッキーがない場合は
// こうするしかない）
if ($_conf['disable_cookie'] && ini_get('session.use_only_cookies')) {
    ini_set('session.use_only_cookies', 0);
}

// 名前は、セッションクッキーを破棄するときのために、セッション利用の有無に関わらず設定する
session_name('PS');

if (defined('P2_FORCE_USE_SESSION') || $_conf['expack.misc.multi_favs']) {
    $_conf['use_session'] = 1;
}

$_conf['sid_at_a'] = '';

if ($_conf['use_session'] == 1 or ($_conf['use_session'] == 2 && !$_COOKIE['cid'])) {
    require_once $P2_LIB_DIR_S . 'Session.php';

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

    if (!$support_cookies) {
        if (ini_get('session.use_only_cookies')) {
            p2die('Session unavailable', 'php.ini で session.use_only_cookies が On になっています。');
        }
        if (!ini_get('session.use_trans_sid')) {
            output_add_rewrite_var(session_name(), session_id());
            $_conf['sid_at_a'] = '&amp;' . rawurldecode(session_name()) . '=' . rawurldecode(session_id());
        }
    }
}

// }}}
// {{{ お気にセット

// 複数のお気にセットを使うとき
if ($_conf['expack.misc.multi_favs']) {
    if (!class_exists('FavSetManager', false)) {
        include $P2_LIB_DIR_S . 'FavSetManager.php';
    }
    // 切り替え表示用に全てのお気にスレ・お気に板を読み込んでおく
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

// DOCTYPE HTML 宣言
$_conf['doctype'] = '';
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

// XHTMLヘッダ要素
if (defined('P2_OUTPUT_XHTML')) {
    $_conf['extra_headers_xht'] = preg_replace('/<((?:link|meta) .+?)>/', '<\\1 />', $_conf['extra_headers_ht']);
}

// ログインクラスのインスタンス生成（ログインユーザが指定されていなければ、この時点でログインフォーム表示に）
require_once $P2_LIB_DIR_S . 'Login.php';
$_login = new Login();

// おまじない
//$a = ceil(1/2);
//$b = floor(1/3);
//$c = round(1/4, 1);

// }}}
}

// }}}
// {{{ p2checkenv()

/**
 * 動作環境を確認する
 *
 * @return bool
 */
function p2checkenv($check_recommended)
{
    global $_info_msg_ht;

    $php_version = phpversion();
    $required_version = '5.2.8';
    $recommended_version = '5.2.10';

    // PHPのバージョン
    if (version_compare($php_version, $required_version, '<')) {
        p2die("PHP {$required_version} 未満では使えません。");
    }

    // 必須拡張モジュール
    foreach (array('json', 'mbstring', 'pcre', 'pdo', 'pdo_sqlite', 'session', 'zlib') as $ext) {
        if (!extension_loaded($ext)) {
            p2die("{$ext} 拡張モジュールがロードされていません。");
        }
    }

    // セーフモード
    if (ini_get('safe_mode')) {
        p2die('セーフモードで動作するPHPでは使えません。');
    }

    // register_globals
    if (ini_get('register_globals')) {
        $msg = <<<EOP
予期しない動作を避けるために php.ini で register_globals を Off にしてください。
magic_quotes_gpc や mbstring.encoding_translation も Off にされることをおすすめします。
EOP;
        p2die('register_globals が On です。', $msg);
    }

    // eAccelerator
    if (extension_loaded('eaccelerator') && version_compare(EACCELERATOR_VERSION, '0.9.5.2', '<')) {
        $err = 'eAcceleratorを更新してください。';
        $ev = EACCELERATOR_VERSION;
        $msg = <<<EOP
<p>PHP 5.2で例外を捕捉できない問題のあるeAccelerator ({$ev})がインストールされています。<br>
eAcceleratorを無効にするか、この問題が修正されたeAccelerator 0.9.5.2以降を使用してください。<br>
<a href="http://eaccelerator.net/">http://eaccelerator.net/</a></p>
EOP;
        p2die($err, $msg, true);
    }

    // 推奨バージョン
    if ($check_recommended && version_compare($php_version, $recommended_version, '<')) {
        $conf_php = htmlspecialchars(__FILE__, ENT_QUOTES);
        $_info_msg_ht .= <<<EOP
<p><strong>推奨バージョンより古いPHPで動作しています。</strong><em>(PHP {$php_version})</em><br>
PHP {$recommended_version} 以降にアップデートすることをおすすめします。<br>
<small>（このメッセージを表示しないようにするには {$conf_php} の {$check_recommended} 行目の
&quot;p2checkenv(__LINE__);&quot; を quot;p2checkenv(false);&quot; に書き換えてください）</small></p>
EOP;
        return false;
    }

    return true;
}

// }}}
// {{{ __autoload()

/**
 * PEARで第2引数をfalseにせずにclass_exists()を読んでいる可能性があるので
 * __autoload()を使うのは怖い
 */
/*function __autoload($name)
{
    if (preg_match('/^[A-Za-z_][0-9A-Za-z_]*$/', $name)) {
        require_once str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
    }
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
