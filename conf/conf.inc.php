<?php
/*
    rep2 - 基本設定ファイル

    このファイルはシステム内部設定用です。特に理由の無い限り変更しないで下さい。
    ユーザ設定は、ブラウザ上から「ユーザ設定編集」で変更可能です。
    管理者向け設定はファイル conf/conf_admin.inc.php を直接書き換えて下さい。
*/


// システム設定を読み込み
if (!include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'conf_system.inc.php') {
    die("p2 error: 管理者用設定ファイルを読み込めませんでした。");
}

// 以下、ユーザ対象の設定
//（conf_user.inc.phpを作ってまとめたいが、昔使っていたファイル名とかぶるので迷っている）

// {{{ ユーザ設定 読込

// デフォルト設定（conf_user_def.inc.php）を読み込む
require_once P2_CONF_DIR . DIRECTORY_SEPARATOR . 'conf_user_def.inc.php';
$_conf = array_merge($_conf, $conf_user_def);

// ユーザ設定があれば読み込む
$_conf['conf_user_file'] = $_conf['pref_dir'] . '/conf_user.srd.cgi';

// 2006-02-27 旧形式ファイルがあれば変換してコピー
//_copyOldConfUserFileIfExists();

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
$_conf['cookie_dir']            = $_conf['pref_dir'] . '/p2_cookie'; // cookie 保存ディレクトリ

// 最近読んだスレ
$_conf['recent_file']           = $_conf['pref_dir'] . '/p2_recent.idx';
// 互換用
$_conf['recent_idx']            = $_conf['recent_file'];

$_conf['res_hist_idx']          = $_conf['pref_dir'] . '/p2_res_hist.idx';      // 書き込みログ (idx)

// 書き込みログファイル（dat）
$_conf['p2_res_hist_dat']       = $_conf['pref_dir'] . '/p2_res_hist.dat';

// 書き込みログファイル（データPHP）旧
$_conf['p2_res_hist_dat_php']   = $_conf['pref_dir'] . '/p2_res_hist.dat.php';

// 書き込みログファイル（dat） セキュリティ通報用
$_conf['p2_res_hist_dat_secu']  = $_conf['pref_dir'] . '/p2_res_hist.secu.cgi';

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


// {{{ ありえない引数のエラー

// 新規ログインとメンバーログインの同時指定はありえないので、エラー出す
if (isset($_POST['submit_newuser']) && isset($_POST['submit_userlogin'])) {
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
// （[todo]この処理を上に持って行きたいが、ユーザーログインか新規登録どうかの区別ができなくなる。
// login_first.inc.phpのfile_exists($_conf['auth_user_file']) で新規登録かどうかを判定しているのを改める必要がある)
$_login = new Login;

// このファイル内での処理はここまで


//=============================================================================
// 関数（このファイル内でのみ利用）
//=============================================================================

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
 * @return  Session|null|die
 */
function _startSession()
{
    global $_conf;
    
    // 名前は、セッションクッキーを破棄するときのために、セッション利用の有無に関わらず設定する
    session_name('PS');

    $cookie = session_get_cookie_params();
    session_set_cookie_params($cookie['lifetime'], '/', P2Util::getCookieDomain(), $secure = false);
    
    // css.php は特別にセッションから外す。
    //if (basename($_SERVER['SCRIPT_NAME']) == 'css.php') {
    //    return null;
    //}
    
    if ($_conf['use_session'] == 1 or ($_conf['use_session'] == 2 && empty($_COOKIE['cid']))) { 

        if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
            _prepareFileSession();
        }

        return new Session;
    }
    return null;
}

/**
 * @return  void
 */
function _prepareFileSession()
{
    global $_conf;
    
    // セッションデータ保存ディレクトリを設定
    if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
        // $_conf['data_dir'] を絶対パスに変換する
        define('P2_DATA_DIR_REAL_PATH', File_Util::realPath($_conf['data_dir']));
        $_conf['session_dir'] = P2_DATA_DIR_REAL_PATH . DIRECTORY_SEPARATOR . 'session';
    }
    
    if (!is_dir($_conf['session_dir'])) {
        require_once P2_LIB_DIR . '/FileCtl.php';
        FileCtl::mkdirFor($_conf['session_dir'] . '/dummy_filename');
    }
    if (!is_writable($_conf['session_dir'])) {
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
