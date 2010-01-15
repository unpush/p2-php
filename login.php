<?php
/**
 * p2 ログイン
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

//=========================================================
// 書き出し用変数
//=========================================================
$p_htm = array();

// 表示文字
$p_str = array(
    'ptitle'        => 'rep2認証ユーザ管理',
    'autho_user'    => '認証ユーザ',
    'logout'        => 'ログアウト',
    'password'      => 'パスワード',
    'login'         => 'ログイン',
    'user'          => 'ユーザ'
);

// 携帯用表示文字列変換
if ($_conf['ktai'] && function_exists('mb_convert_kana')) {
    foreach ($p_str as $k => $v) {
        $p_str[$k] = mb_convert_kana($v, 'rnsk');
    }
}

// {{{ （携帯）ログイン用URL
/*
$qs = array();
if ($_conf['ktai']) {
    $qs['user'] = $_login->user_u;
}
$qs[UA::getQueryKey()] = UA::getMobileQuery();
$atag = P2View::tagA(
    $uri = P2Util::buildQueryUri(
        rtrim(dirname(P2Util::getMyUrl()), '/') . '/',
        $qs
    ),
    $uri,
    array('target' => '_blank')
);
$ktai_url_ht   = sprintf('携帯%s用URL %s<br>', hs($p_str['login']), $atag);
*/
// }}}

$csrfid = P2Util::getCsrfId();
$hr = P2View::getHrHtmlK();


// パスワード変更登録処理
_preExecChangePass();


//====================================================
// 補助認証
//====================================================
$auth_ctl_html = '';
$auth_cookie_html = '';

$mobile = &Net_UserAgent_Mobile::singleton();
require_once P2_LIB_DIR . '/HostCheck.php';

// EZ認証
if (!empty($_SERVER['HTTP_X_UP_SUBNO'])) {
    if ($_login->hasRegistedAuthCarrier('EZWEB')) {
        $atag = P2View::tagA(
            P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'],
                array(
                    'ctl_regist_ez' => '1',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            '解除'
        );
        $auth_ctl_html = sprintf('EZ端末ID認証登録済[%s]<br>', $atag);

    } else {
        if ($_login->pass_x) {
            $atag = P2View::tagA(
                P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'],
                    array(
                        'ctl_regist_ez' => '1',
                        'regist_ez' => '1',
                        UA::getQueryKey() => UA::getQueryValue()
                    )
                ),
                'EZ端末IDで認証を登録'
            );
            $auth_ctl_html = sprintf('[%s]<br>', $atag);
        }
    }

// SoftBank認証
} elseif (HostCheck::isAddrSoftBank() && P2Util::getSoftBankID()) {
    if ($_login->hasRegistedAuthCarrier('SOFTBANK')) {
        $atag = P2View::tagA(
            P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'],
                array(
                    'ctl_regist_jp' => '1',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            '解除'
        );
        $auth_ctl_html = sprintf('SoftBank端末ID認証登録済[%s]<br>', $atag);

    } else {
        if ($_login->pass_x) {
            $atag = P2View::tagA(
                P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'],
                    array(
                        'ctl_regist_jp' => '1',
                        'regist_jp' => '1',
                        UA::getQueryKey() => UA::getQueryValue()
                    )
                ),
                'SoftBank端末IDで認証を登録'
            );
            $auth_ctl_html = sprintf('[%s]<br>', $atag);
        }
    }
    
// docomo認証
} elseif ($mobile->isDoCoMo()) {
    if ($_login->hasRegistedAuthCarrier('DOCOMO')) {
        $atag = P2View::tagA(
            P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'],
                array(
                    'ctl_regist_docomo' => '1',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            '解除'
        );
        $auth_ctl_html = sprintf('docomo端末ID認証登録済[%s]<br>', $atag);

    } else {
        if ($_login->pass_x) {
            $uri = P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'],
                array(
                    'ctl_regist_docomo' => '1',
                    'regist_docomo' => '1',
                    'guid' => 'ON',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            );
            $atag = sprintf('<a href="%s" utn>%s</a>', $uri, 'docomo端末IDで認証を登録');
            $auth_ctl_html = sprintf('[%s]<br>', $atag);
        }
    }
    
// Cookie認証
} else {
    if ($_login->checkUserPwWithCid($_COOKIE['cid'])) {
        $atag = P2View::tagA(
            P2Util::buildQueryUri('cookie.php',
                array(
                    'ctl_regist_cookie' => '1',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            '解除'
        );
        $auth_cookie_html = sprintf('cookie認証登録済[%s]<br>', $atag);
        
    } else {
        if ($_login->pass_x) {
            $atag = P2View::tagA(
                P2Util::buildQueryUri('cookie.php',
                    array(
                        'ctl_regist_cookie' => '1',
                        'regist_cookie' => '1',
                        UA::getQueryKey() => UA::getQueryValue()
                    )
                ),
                'cookieに認証を登録'
            );
            $auth_cookie_html = sprintf('[%s]<br>', $atag);
        }
    }
}

// Cookie認証登録解除処理
_preExecCheckRegistCookie();

//=================================================================
// HTMLプリント
//=================================================================
$p_htm['body_onload'] = '';
if (!$_conf['ktai']) {
    $p_htm['body_onload'] = ' onLoad="setWinTitle();"';
}

$body_at = P2View::getBodyAttrK();


P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
    <title><?php eh($p_str['ptitle']); ?></title>
<?php
if (!$_conf['ktai']) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('login');
    ?>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script type="text/javascript" src="js/basic.js?v=20090429"></script>
<?php
}
echo <<<EOP
</head>
<body{$p_htm['body_onload']}{$body_at}>
EOP;

if (!$_conf['ktai']) {
    ?>
<p id="pan_menu"><a href="setting.php">ログイン管理</a> &gt; <?php eh($p_str['ptitle']); ?></p>
<?php
}

P2Util::printInfoHtml();
?>
<p id="login_status">
<?php eh($p_str['autho_user']) ?>: <?php eh($_login->user_u) ?><br>
<?php echo $auth_ctl_html, $auth_cookie_html ?>
<br>
[<a href="./index.php?logout=1" target="_parent">rep2から<?php eh($p_str['logout']); ?>する</a>]
</p>

<?php
// 認証ユーザ登録フォーム
if ($_conf['ktai']) {
    echo $hr;
}
?>
<form id="login_change" method="POST" action="<?php eh($_SERVER['SCRIPT_NAME']) ?>" target="_self">
    <input type="hidden" name="csrfid" value="<?php eh($csrfid) ?>">
    <?php eh($p_str['password']) ?>の変更<br>
    <?php echo P2View::getInputHiddenKTag(); ?>
    新しい<?php eh($p_str['password']) ?>: <input type="password" name="form_login_pass">
    <br>
    <input type="submit" name="submit" value="変更登録">
</form>

<?php
if (UA::isK()) {
    echo "$hr\n";
    echo P2View::getBackToIndexKATag();
}
?>
</body></html>
<?php

exit;


//================================================================================
// 関数（このファイル内でのみ利用）
//================================================================================
/**
 * パスワード変更登録処理
 *
 * @return  void or P2Util::pushInfoHtml() or die
 */
function _preExecChangePass()
{
    global $_login;
    
    if (isset($_POST['form_login_pass'])) {

        // 入力チェック
        if (!isset($_POST['csrfid']) || $_POST['csrfid'] != P2Util::getCsrfId()) {
            P2Util::pushInfoHtml('<p>p2 error: 不正なPOSTです</p>');
        
        } elseif (!preg_match('/^[0-9a-zA-Z_]+$/', $_POST['form_login_pass'])) {
            P2Util::pushInfoHtml(
                '<p>p2 error: パスワードを半角英数字で入力して下さい。</p>'
            );
        
        // パスワード変更登録処理を行う
        } else {

            if (!$_login->savaRegistUserPass($_login->user_u, $_POST['form_login_pass'])) {
                p2die('ユーザ登録処理を完了できませんでした。');
            }
            
            P2Util::pushInfoHtml('<p>○認証パスワードを変更登録しました</p>');
        }
    }
}

/**
 * Cookie認証登録解除処理の結果
 *
 * @return  void, P2Util::pushInfoHtml()
 */
function _preExecCheckRegistCookie()
{
    global $_login;
    
    if (isset($_REQUEST['check_regist_cookie'])) {

        if ($_login->checkUserPwWithCid($_COOKIE['cid'])) {
            if (geti($_REQUEST['regist_cookie']) == '1') {
                P2Util::pushInfoHtml('<p>○cookie認証登録完了</p>');
            } else {
                P2Util::pushInfoHtml('<p>×cookie認証解除失敗</p>');
            }
        
        } else {
            if (geti($_REQUEST['regist_cookie']) == '1') {
                P2Util::pushInfoHtml('<p>×cookie認証登録失敗</p>');
            } else  {
                P2Util::pushInfoHtml('<p>○cookie認証解除完了</p>');
            }
        }
    }
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
