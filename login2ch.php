<?php
/**
 *    p2 - 2ch●ログイン管理
 */

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/P2Validate.php';

$_login->authorize(); // ユーザ認証

//================================================================
// 変数
//================================================================
$login2chID   = geti($_POST['login2chID']);
$login2chPW   = geti($_POST['login2chPW']);
$autoLogin2ch = intval(geti($_POST['autoLogin2ch']));

// 2ch ID (メアド)
if ($login2chID and P2Validate::mail($login2chID)) {
    P2Util::pushInfoHtml('<p>p2 error: 使用できないID文字列が含まれています</p>');
    $login2chID = null;
}

// 正確な許可文字列は不明
if ($login2chPW and P2Validate::login2chPW($login2chPW)) {
    P2Util::pushInfoHtml('<p>p2 error: 使用できないパスワード文字列が含まれています</p>');
    $login2chPW = null;
}

//===============================================================
// ログインなら、IDとPWを登録保存して、ログインする
//===============================================================
if ($login2chID && $login2chPW) {

    P2Util::saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch);

    require_once P2_LIB_DIR . '/login2ch.inc.php';
    login2ch();
}

// （フォーム入力用に）ID, PW設定を読み込む
if ($array = P2Util::readIdPw2ch()) {
    list($login2chID, $login2chPW, $autoLogin2ch) = $array;
}

// {{{ 2chログイン処理

if (isset($_GET['login2ch'])) {
    if ($_GET['login2ch'] == "in") {
        require_once P2_LIB_DIR . '/login2ch.inc.php';
        login2ch();
    } elseif ($_GET['login2ch'] == "out") {
        if (file_exists($_conf['sid2ch_php'])) {
            unlink($_conf['sid2ch_php']);
        }
    }
}

// }}}

$hr = P2View::getHrHtmlK();

//================================================================
// ヘッダ
//================================================================
if ($_conf['ktai']) {
    $login_st       = "ﾛｸﾞｲﾝ";
    $logout_st      = "ﾛｸﾞｱｳﾄ";
    $password_st    = "ﾊﾟｽﾜｰﾄﾞ";
} else {
    $login_st       = "ログイン";
    $logout_st      = "ログアウト";
    $password_st    = "パスワード";
}

if (file_exists($_conf['sid2ch_php'])) { // 2ch●書き込み
    $ptitle = "●2ch{$login_st}管理";
} else {
    $ptitle = "2ch{$login_st}管理";
}

$body_at = P2View::getBodyAttrK();

if (!$_conf['ktai']) {
    $body_at .= " onLoad=\"setWinTitle();\"";
}

P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
echo <<<EOP
    <title>{$ptitle}</title>
EOP;

if (!$_conf['ktai']) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('login2ch');
    echo <<<EOP
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script type="text/javascript" src="js/basic.js?v=20090429"></script>
EOP;
}

echo <<<EOP
    <script type="text/javascript">
    <!--
    function checkPass2ch(){ 
        if (pass2ch_input = document.getElementById('login2chPW')) {
            if (pass2ch_input.value == "") {
                alert("パスワードを入力して下さい");
                return false;
            }
        }
    }
    // -->
    </script>
</head>
<body{$body_at}>
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu"><a href="setting.php">ログイン管理</a> &gt; {$ptitle}</p>
EOP;
}

P2Util::printInfoHtml();

//================================================================
// 2ch●ログインフォーム
//================================================================

// ログイン中なら
if (file_exists($_conf['sid2ch_php'])) {
    $idsub_str = "再{$login_st}する";
    $form_now_login_ht = <<<EOFORM
    <form id="form_logout" method="GET" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
        現在、2ちゃんねるに{$login_st}中です 
        {$_conf['k_input_ht']}
        <input type="hidden" name="login2ch" value="out">
        <input type="submit" name="submit" value="{$logout_st}する">
    </form>\n
EOFORM;

} else {
    $idsub_str = "新規{$login_st}する";
    if (file_exists($_conf['idpw2ch_php'])) {
        $form_now_login_ht = <<<EOFORM
    <form id="form_logout" method="GET" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
        現在、{$login_st}していません 
        {$_conf['k_input_ht']}
        <input type="hidden" name="login2ch" value="in">
        <input type="submit" name="submit" value="再{$login_st}する">
    </form>\n
EOFORM;
    } else {
        $form_now_login_ht = "<p>現在、{$login_st}していません</p>";
    }
}

if ($autoLogin2ch) {
    $autoLogin2ch_checked = ' checked="true"';
} else {
    $autoLogin2ch_checked = '';
}

$tora3_url = "http://2ch.tora3.net/";
$tora3_url_r = P2Util::throughIme($tora3_url);

if (!$_conf['ktai']) {
    $id_input_size_at = ' size="30"';
    $pass_input_size_at = ' size="24"';
} else {
    $id_input_size_at = '';
    $pass_input_size_at = '';
}

// HTMLプリント
?>
<div id="login_status">
<?php echo $form_now_login_ht; ?>
</div>
<?php
if ($_conf['ktai']) {
    echo $hr;
}
?>
<form id="login_with_id" method="POST" action="<?php eh($_SERVER['SCRIPT_NAME']); ?>" target="_self">
    <?php echo P2View::getInputHiddenKTag(); ?>
    ID: <input type="text" name="login2chID" value="<?php eh($login2chID); ?>"<?php echo $id_input_size_at; ?>><br>
    <?php eh($password_st); ?>: <input type="password" name="login2chPW" id="login2chPW"<?php echo $pass_input_size_at; ?>><br>
    <input type="checkbox" id="autoLogin2ch" name="autoLogin2ch" value="1"<?php echo $autoLogin2ch_checked; ?>><label for="autoLogin2ch">起動時に自動<?php eh($login_st); ?>する</label><br>
    <input type="submit" name="submit" value="<?php eh($idsub_str); ?>" onClick="return checkPass2ch();">
</form>
<?php

if ($_conf['ktai']) {
    echo $hr;
}

//================================================================
// フッタHTML表示
//================================================================

printf(
    '<p>2ch IDについての詳細はこちら→ <a href="%s" target="_blank">%s</a></p>',
    hs($tora3_url_r),
    hs($tora3_url)
);

if (UA::isK()) {
    echo $hr;
    echo P2View::getBackToIndexKATag();
}

?></body></html><?php


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
