<?php
/**
 * rep2expack - 初期化スクリプト
 * conf/conf.inc.php の p2configure() から読み込まれる。
 */

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

    // 簡易スクリプトインジェクション対策
    foreach (array('host', 'bbs', 'key', 'ls') as $_k) {
        if (array_key_exists($_k, $_REQUEST)) {
            $_v = $_REQUEST[$_k];
            if (htmlspecialchars($_v, ENT_QUOTES) != $_v) {
                p2die('リクエストパラメータに不正な文字があります。');
            }
        }
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

$skin_name = $default_skin_name = 'conf_user_style';
$skin = $P2_CONF_DIR_S . 'conf_user_style.inc.php';
if (!$_conf['ktai'] && $_conf['expack.skin.enabled']) {
    // 保存されているスキン名
    $saved_skin_name = null;
    if (file_exists($_conf['expack.skin.setting_path'])) {
        $saved_skin_name = rtrim(file_get_contents($_conf['expack.skin.setting_path']));
        if (!preg_match('/^[0-9A-Za-z_\\-]+$/', $saved_skin_name)) {
            $saved_skin_name = null;
        }
    } else {
        FileCtl::make_datafile($_conf['expack.skin.setting_path'], $_conf['expack.skin.setting_perm']);
    }

    // リクエストで指定されたスキン名
    $new_skin_name = null;
    if (array_key_exists('skin', $_REQUEST) && is_string($_REQUEST['skin'])) {
        $new_skin_name = $_REQUEST['skin'];
        if (!preg_match('/^[0-9A-Za-z_\\-]+$/', $new_skin_name)) {
            $new_skin_name = null;
        } elseif ($new_skin_name != $saved_skin_name) {
            FileCtl::file_write_contents($_conf['expack.skin.setting_path'], $new_skin_name);
        }
    }

    // リクエストで指定された一時スキン名
    $tmp_skin_name = null;
    if (array_key_exists('tmp_skin', $_REQUEST) && is_string($_REQUEST['tmp_skin'])) {
        $tmp_skin_name = $_REQUEST['tmp_skin'];
        if (!preg_match('/^[0-9A-Za-z_\\-]+$/', $tmp_skin_name)) {
            $tmp_skin_name = null;
        }
    }

    // スキン検索
    foreach (array($tmp_skin_name, $new_skin_name, $saved_skin_name, $default_skin_name) as $skin_name) {
        if ($skin_name !== null) {
            if ($skin_name == $default_skin_name) {
                break;
            }
            $user_skin_path = P2_USER_SKIN_DIR . DIRECTORY_SEPARATOR . $skin_name . '.php';
            if (file_exists($user_skin_path)) {
                $skin = $user_skin_path;
                break;
            }
            $bundled_skin_path = P2_SKIN_DIR . DIRECTORY_SEPARATOR . $skin_name . '.php';
            if (file_exists($bundled_skin_path)) {
                $skin = $bundled_skin_path;
                break;
            }
        }
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

// 名前は、セッションクッキーを破棄するときのために、セッション利用の有無に関わらず設定する
session_name('PS');

$_conf['sid_at_a'] = '';

require_once $P2_LIB_DIR_S . 'Session.php';

// {{{ セッションデータ保存ディレクトリをチェック

if ($_conf['session_save'] == 'p2' and session_module_name() == 'files') {
    if (!is_dir($_conf['session_dir'])) {
        FileCtl::mkdir_r($_conf['session_dir']);
    } elseif (!is_writable($_conf['session_dir'])) {
        p2die("セッションデータ保存ディレクトリ ({$_conf['session_dir']}) に書き込み権限がありません。");
    }

    session_save_path($_conf['session_dir']);
}

// }}}

$_p2session = new Session(null, null, $support_cookies);

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
