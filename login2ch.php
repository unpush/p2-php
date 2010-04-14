<?php
/**
 * rep2 - 2ch●ログイン管理
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

//================================================================
// 変数
//================================================================
if (isset($_POST['login2chID']))   { $login2chID = $_POST['login2chID']; }
if (isset($_POST['login2chPW']))   { $login2chPW = $_POST['login2chPW']; }
if (isset($_POST['autoLogin2ch'])) { $autoLogin2ch = $_POST['autoLogin2ch']; }

//===============================================================
// ログインなら、IDとPWを登録保存して、ログインする
//===============================================================
if (isset($_POST['login2chID']) && isset($_POST['login2chPW'])) {

    if (isset($_POST['autoLogin2ch'])) {
        $autoLogin2ch = $_POST['autoLogin2ch'];
    } else {
        $autoLogin2ch = 0;
    }

    P2Util::saveIdPw2ch($_POST['login2chID'], $_POST['login2chPW'], $autoLogin2ch);

    require_once P2_LIB_DIR . '/login2ch.inc.php';
    login2ch();
}

// （フォーム入力用に）ID, PW設定を読み込む
if ($array = P2Util::readIdPw2ch()) {
    list($login2chID, $login2chPW, $autoLogin2ch) = $array;
}

//==============================================================
// 2chログイン処理
//==============================================================
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

//================================================================
// ヘッダ
//================================================================
if ($_conf['ktai']) {
    $login_st = "ﾛｸﾞｲﾝ";
    $logout_st = "ﾛｸﾞｱｳﾄ";
    $password_st = "ﾊﾟｽﾜｰﾄﾞ";
} else {
    $login_st = "ログイン";
    $logout_st = "ログアウト";
    $password_st = "パスワード";
}

if (file_exists($_conf['sid2ch_php'])) { // 2ch●書き込み
    $ptitle = "●2ch{$login_st}管理";
} else {
    $ptitle = "2ch{$login_st}管理";
}

P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>\n
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=login2ch&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>\n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : ' onload="setWinTitle();"';

echo <<<EOP
    <script type="text/javascript">
    //<![CDATA[
    function checkPass2ch(){
        if (pass2ch_input = document.getElementById('login2chPW')) {
            if (pass2ch_input.value == "") {
                alert("パスワードを入力して下さい");
                return false;
            }
        }
    }
    //]]>
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
    $form_now_log = <<<EOFORM
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
        $form_now_log = <<<EOFORM
    <form id="form_logout" method="GET" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
        現在、{$login_st}していません
        {$_conf['k_input_ht']}
        <input type="hidden" name="login2ch" value="in">
        <input type="submit" name="submit" value="再{$login_st}する">
    </form>\n
EOFORM;
    } else {
        $form_now_log = "<p>現在、{$login_st}していません</p>";
    }
}

if ($autoLogin2ch) {
    $autoLogin2ch_checked = " checked=\"true\"";
}

$tora3_url = "http://2ch.tora3.net/";
$tora3_url_r = P2Util::throughIme($tora3_url);

if (!$_conf['ktai']) {
    $id_input_size_at = " size=\"30\"";
    $pass_input_size_at = " size=\"24\"";
}

// プリント =================================
echo "<div id=\"login_status\">";
echo $form_now_log;
echo "</div>";

if ($_conf['ktai']) {
    echo "<hr>";
}

echo <<<EOFORM
<form id="login_with_id" method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
    {$_conf['k_input_ht']}
    ID: <input type="text" name="login2chID" value="{$login2chID}"{$id_input_size_at}><br>
    {$password_st}: <input type="password" name="login2chPW" id="login2chPW"{$pass_input_size_at}><br>
    <input type="checkbox" id="autoLogin2ch" name="autoLogin2ch" value="1"{$autoLogin2ch_checked}><label for="autoLogin2ch">起動時に自動{$login_st}する</label><br>
    <input type="submit" name="submit" value="{$idsub_str}" onclick="return checkPass2ch();">
</form>\n
EOFORM;

if ($_conf['ktai']) {
    echo "<hr>";
}

//================================================================
// フッタHTML表示
//================================================================

echo <<<EOP
<p>2ch IDについての詳細はこちら→ <a href="{$tora3_url_r}" target="_blank">{$tora3_url}</a></p>
EOP;

if ($_conf['ktai']) {
    echo "<hr><div class=\"center\">{$_conf['k_to_index_ht']}</div>";
}

echo '</body></html>';

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
