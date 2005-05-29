<?php
/*
    p2 ログイン
*/

include_once './conf/conf.inc.php';  // 基本設定
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

authorize(); //ユーザ認証

if (!$login['use']) {
    die("p2 info: 現在、ユーザ認証は「利用しない」設定になっています。<br>この機能を管理するためには、まず conf.inc.php で設定を有効にして下さい。");
}

//=========================================================
// 書き出し用変数
//=========================================================
$ptitle = "p2認証ユーザ管理";

$autho_user_ht = "";
$auth_ctl_ht = "";
$auth_sub_input_ht = "";
$ivalue_user = "";

if ($_conf['ktai']) {
    $status_st = "ｽﾃｰﾀｽ";
    $autho_user_st = "認証ﾕｰｻﾞ";
    $client_host_st = "端末ﾎｽﾄ";
    $client_ip_st = "端末IPｱﾄﾞﾚｽ";
    $browser_ua_st = "ﾌﾞﾗｳｻﾞUA";
    $p2error_st = "p2 ｴﾗｰ";
    
    $user_st = "ﾕｰｻﾞ";
    $password_st = "ﾊﾟｽﾜｰﾄﾞ";
} else {
    $status_st = "ステータス";
    $autho_user_st = "認証ユーザ";
    $client_host_st = "端末ホスト";
    $client_ip_st = "端末IPアドレス";
    $browser_ua_st = "ブラウザUA";
    $p2error_st = "p2 エラー";
    
    $user_st = "ユーザ";
    $password_st = "パスワード";
}


if ($login['use']) {
    $autho_user_ht = "{$autho_user_st}: {$login['user']}<br>";
}

// 補助認証 =====================================
// EZ認証 ===============
if (!is_null($_SERVER['HTTP_X_UP_SUBNO'])) {
    if (file_exists($_conf['auth_ez_file'])) {
        $auth_ctl_ht=<<<EOP
EZ端末ID認証登録済[<a href="{$_SERVER['PHP_SELF']}?regist_ez=out{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if (!is_null($_SERVER['PHP_AUTH_USER'])) {
            $auth_ctl_ht = <<<EOP
[<a href="{$_SERVER['PHP_SELF']}?regist_ez=in{$_conf['k_at_a']}">EZ端末IDで認証を登録</a>]<br>
EOP;
        }
        $auth_sub_input_ht = <<<EOP
    <input type="checkbox" name="regist_ez" value="in" checked>EZ端末IDで認証を登録<br>
EOP;
    }

// J認証 ================
} elseif ($mobile->isVodafone() && $mobile->getSerialNumber()) {
    if (file_exists($_conf['auth_jp_file'])) {
        $auth_ctl_ht=<<<EOP
J端末ID認証登録済[<a href="{$_SERVER['PHP_SELF']}?regist_jp=out{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if (!is_null($_SERVER['PHP_AUTH_USER'])) {
            $auth_ctl_ht = <<<EOP
[<a href="{$_SERVER['PHP_SELF']}?regist_jp=in{$_conf['k_at_a']}">J端末IDで認証を登録</a>]<br>
EOP;
        }
        $auth_sub_input_ht = <<<EOP
    <input type="checkbox" name="regist_jp" value="in" checked>J端末IDで認証を登録<br>
EOP;
    }
    
// Cookie認証================
} else {
    if (($_COOKIE['p2_user'] == $login['user']) && ($_COOKIE['p2_pass'] == $login['pass'])) {
            $auth_cookie_ht = <<<EOP
cookie認証登録済[<a href="cookie.php?ctl_regist_cookie=1{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if (!is_null($_SERVER['PHP_AUTH_USER'])) {
            $auth_cookie_ht = <<<EOP
[<a href="cookie.php?ctl_regist_cookie=1&amp;regist_cookie=1{$_conf['k_at_a']}">cookieで認証を登録</a>]<br>
EOP;
        }
    }
}

//====================================================
// Cookie認証チェック
//====================================================
if (!empty($_REQUEST['check_regist_cookie'])) {
    if (($_COOKIE['p2_user'] == $login['user']) && ($_COOKIE['p2_pass'] == $login['pass'])) {
        if ($_REQUEST['regist_cookie'] == '1') {
            $_info_msg_ht .= "<p>○cookie認証登録完了</p>";
        } else {
            $_info_msg_ht .= "<p>×cookie認証解除失敗</p>";
        }
    } else {
        if ($_REQUEST['regist_cookie'] == '1') {
            $_info_msg_ht .= '<p>×cookie認証登録失敗</p>';
        } else  {
            $_info_msg_ht .= '<p>○cookie認証解除完了</p>';
        }
    }
}


// 認証ユーザ設定読み込み ========
if (file_exists($_conf['auth_user_file'])) {
    include($_conf['auth_user_file']);    
    if (isset($login['user'])) {
        $ivalue_user = $login['user'];
    }
}
if (isset($_POST['login_user'])) {
    $ivalue_user=$_POST['login_user'];
}
    
// 認証ユーザ登録フォーム================
$login_form_ht = <<<EOP
<form id="login_change" method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
    認証{$user_st}名と{$password_st}の変更<br>
    {$_conf['k_input_ht']}
    {$user_st}: <input type="text" name="login_user" value="{$ivalue_user}"><br>
    {$password_st}: <input type="password" name="login_pass"><br>
    {$auth_sub_input_ht}
    <br>
    <input type="submit" name="submit" value="変更登録">
</form>\n
EOP;

if ($_conf['ktai']) {
    $login_form_ht = '<hr>'.$login_form_ht;
}

// ユーザ登録処理=================================
if ($_POST['login_user'] && $_POST['login_pass']) {

    if (!preg_match('/^[0-9a-zA-Z_]+$/', $_POST['login_user']) || !preg_match('/^[0-9a-zA-Z_]+$/', $_POST['login_pass'])) {
        $_info_msg_ht .= "<p>p2 error: {$user_st}名と{$password_st}は半角英数字で入力して下さい。</p>";

    } else {
        $crypted_login_pass = crypt($_POST['login_pass'], $_POST['login_pass']);
        $auth_user_cont = <<<EOP
<?php
\$login['user'] = '{$_POST["login_user"]}';
\$login['pass'] = '{$crypted_login_pass}';
?>
EOP;
        FileCtl::make_datafile($_conf['auth_user_file'], $_conf['pass_perm']); // ファイルがなければ生成
        $fp = @fopen($_conf['auth_user_file'], "wb") or die("p2 Error: {$_conf['auth_user_file']} を保存できませんでした。認証ユーザ登録失敗。");
        @flock($fp, LOCK_EX);
        fputs($fp, $auth_user_cont);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        $_info_msg_ht.="<p>○認証{$user_st}「{$_POST['login_user']}」を登録しました</p>";
    }
    
} else {
    
    if ($_POST['login_user'] || $_POST['login_pass']) {
        if (!$_POST['login_user']) {
            $_info_msg_ht .= "<p>p2 error: {$user_st}名が入力されていません。</p>";
        } elseif (!$_POST['login_pass']) {
            $_info_msg_ht .= "<p>p2 error: {$password_st}が入力されていません。</p>";
        }
    }
    
}

$body_onload = "";
if (!$_conf['ktai']) {
    $body_onload = " onLoad=\"setWinTitle();\"";
}

//=========================================================
// HTMLプリント
//=========================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html>
<head>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>
EOP;
if (!$_conf['ktai']) {
    @include("./style/style_css.inc");
    @include("./style/login_css.inc");
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js"></script>
EOP;
}
echo <<<EOP
</head>
<body{$body_onload}>
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu"><a href="setting.php">ログイン管理</a> &gt; {$ptitle}</p>
EOP;
}

echo $_info_msg_ht;
$_info_msg_ht = "";
    
echo "<p id=\"login_status\">";
echo <<<EOP
{$autho_user_ht}
{$auth_ctl_ht}
{$auth_cookie_ht}
EOP;
echo "</p>";

echo $login_form_ht;

if ($_conf['ktai']) {
    echo "<hr>\n";
    echo $_conf['k_to_index_ht'];
}

echo '</body></html>';

?>
