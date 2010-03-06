<?php
// p2 システム設定
// このファイルは、特に理由の無い限り変更しないで下さい。
// include from conf.inc.php

$_conf['p2version'] = '1.8.61'; // rep2のバージョン

$_conf['p2name'] = 'rep2';    // rep2の名前。

$_conf['p2uaname'] = 'r e p 2';  // UA用のrep2の名前

//======================================================================
// 基本設定処理
//======================================================================
// エラー出力設定
_setErrorReporting(); // error_reporting()

// デバッグ用変数を設定
_setDebug(); // void  $GLOBALS['debug'], $GLOBALS['profiler']

// PHPの動作環境を確認
_checkPHPInstalled(); // void|die

// PHPの環境設定
_setPHPEnvironments();

// p2のディレクトリパス定数を設定する
_setP2DirConstants(); // P2_LIB_DIR 等

require_once P2_LIB_DIR . '/global.funcs.php';

// 検索パスをセット
_iniSetIncludePath(); // void

// PEARライブラリを読み込む
_includePears(); // void|die

// PEAR::PHP_CompatでPHP5互換の関数を読み込む
_loadPHPCompat();

require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'UriUtil.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'P2Util.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'DataPhp.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'Session.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'Login.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'UA.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'P2View.php';
require_once P2_LIB_DIR . DIRECTORY_SEPARATOR . 'FileCtl.php';

// }}}

// フォームからの入力（POST, GET）を一括で文字コード変換＆サニタイズ
_convertEncodingAndSanitizePostGet();

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

// 管理者用設定を読み込み
if (!require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'conf_admin.inc.php') {
    P2Util::printSimpleHtml("p2 error: 管理者用設定ファイルを読み込めませんでした。");
    trigger_error('!include_once conf_admin.inc.php', E_USER_ERROR);
    die;
}

ini_set('default_socket_timeout', $_conf['default_socket_timeout']);

// cache 保存ディレクトリ (パーミッションは707)
$_conf['cache_dir'] = $_conf['data_dir'] . '/cache'; // 2005/6/29 $_conf['pref_dir'] . '/p2_cache' より変更

// テンポラリディレクトリ (パーミッションは707)
$_conf['tmp_dir'] = $_conf['data_dir'] . '/tmp';

// 管理用保存ディレクトリ (パーミッションは707)
// 2010/02/01 拡張の設定。使用していない。
$_conf['admin_dir'] = $_conf['data_dir'] . '/admin';

$_conf['accesskey_for_k'] = 'accesskey';

// 端末判定
_checkBrowser(); // $_conf, UA::setForceMode()

// b=pc はまだ全てのリンクへの追加が完了しておらず、機能していない箇所がある。地道に整備していきたい。
// output_add_rewrite_var() は便利だが、出力がバッファされて体感速度が落ちるのが難点。。
// 体感速度を落とさない良い方法ないかな？
_setOldStyleKtaiQuery(); // $_conf['ktai'] 等をセット

// $_conf['expack.use_pecl_http'] の調整
_adjustConfUsePeclHttp(); // UA::isK()


// このファイル内での処理はここまで


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
 * エラー出力設定。error_reporting()
 * （NOTICEは削減中だが、まだ残っていると思う）
 *
 * @return  void
 */
function _setErrorReporting()
{
    $except = E_NOTICE;
    if (defined('E_STRICT')) {
        $except = $except | E_STRICT;
    }
    if (defined('E_DEPRECATED')) {
        $except = $except | E_DEPRECATED;
    }
    error_reporting(E_ALL & ~$except);
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
 * PHPの環境設定
 *
 * @return  void
 */
function _setPHPEnvironments()
{
    // タイムゾーンをセット
    _setTimezone();

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

    // OS別の定数を補完セットする。PATH_SEPARATOR, DIRECTORY_SEPARATOR
    _setOSDefine();

    // 文字コードの指定
    _setEncodings();
}

/**
 * @return  void
 */
function _setTimezone()
{
    if (function_exists('date_default_timezone_set')) { 
        date_default_timezone_set('Asia/Tokyo'); 
    } else { 
        @putenv('TZ=JST-9'); 
    }
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
 * OS別の定数を補完セットする
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
 * p2のディレクトリパス定数を設定する
 *
 * @return  void
 */
function _setP2DirConstants()
{
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
 * @return  void
 */
function _loadPHPCompat()
{
    if (version_compare(phpversion(), '5.0.0', '<')) {
        PHP_Compat::loadFunction('file_put_contents');
        //PHP_Compat::loadFunction('clone');
        PHP_Compat::loadFunction('scandir');
        //PHP_Compat::loadFunction('http_build_query'); // 第3引数に対応するまでは使えない
        //PHP_Compat::loadFunction('array_walk_recursive');
    }
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
 * 端末判定
 *
 * @return  void
 */
function _checkBrowser()
{
    global $_conf;
    
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

        $_conf['ktai'] = true;
        $_conf['disable_cookie'] = false;
        $_conf['accept_charset'] = 'Shift_JIS';

        // ベンダ判定
        // docomo i-Mode
        if ($mobile->isDoCoMo()) {
            // [todo] docomoの新しいのはCookieも使える…
            $_conf['disable_cookie'] = true;
        
        // EZweb (au or Tu-Ka)
        } elseif ($mobile->isEZweb()) {
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

        // WILLCOM（旧AirH"Phone）
        } elseif ($mobile->isWillcom()) {
            $_conf['disable_cookie'] = false;
        }
    }

    // iPhone指定
    if (UA::isIPhoneGroup()) {
        $_conf['ktai'] = true;
        UA::setForceMode(UA::getMobileQuery());

        define('P2_IPHONE_LIB_DIR', './iphone');

        $_conf['subject_php']    = 'subject_i.php';
        $_conf['read_new_k_php'] = 'read_new_i.php';
        $_conf['menu_k_php']     = 'menu_i.php';
        $_conf['editpref_php']   = 'editpref_i.php';
    }
}

/**
 * 旧スタイルの携帯ビュー変数 $_conf['ktai'] 等をセット
 *
 * @return  void
 */
function _setOldStyleKtaiQuery()
{
    global $_conf;
    
    $b = UA::getQueryKey();

    // ?k=1は旧仕様。?b=kが新しい。
    // 後方互換用措置
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
}

/**
 * $_conf['expack.use_pecl_http'] の調整
 *
 * @return  void
 */
function _adjustConfUsePeclHttp()
{
    global $_conf;
    
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
}
