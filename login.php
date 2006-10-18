<?php
/**
 * rep2 ログイン
 */

include_once './conf/conf.inc.php';
require_once P2_LIBRARY_DIR . '/filectl.class.php';

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

// （携帯）ログイン用URL
//$user_u_q = !empty($_conf['ktai']) ? '' : '?user=' . $_login->user_u;
//$url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/' . $user_u_q . '&amp;b=k';
$url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/?b=k';

$p_htm['ktai_url'] = '携帯' . $p_str['login'] . '用URL <a href="' . $url . '" target="_blank">' . $url . '</a><br>';

//====================================================
// ユーザ登録処理
//====================================================
if (isset($_POST['form_login_pass'])) {

    // 入力チェック
    if (!preg_match('/^[0-9a-zA-Z_]+$/', $_POST['form_login_pass'])) {
        $_info_msg_ht .= "<p>rep2 error: {$p_str['password']}を半角英数字で入力して下さい。</p>";

    // パスワード変更登録処理を行う
    } else {
        $crypted_login_pass = sha1($_POST['form_login_pass']);
        $auth_user_cont = <<<EOP
<?php
\$rec_login_user_u = '{$_login->user_u}';
\$rec_login_pass_x = '{$crypted_login_pass}';
?>
EOP;
        FileCtl::make_datafile($_conf['auth_user_file'], $_conf['pass_perm']); // ファイルがなければ生成
        $fp = @fopen($_conf['auth_user_file'], "wb") or die("rep2 Error: {$_conf['auth_user_file']} を保存できませんでした。認証ユーザ登録失敗。");
        @flock($fp, LOCK_EX);
        fputs($fp, $auth_user_cont);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        $_info_msg_ht .= '<p>○認証パスワードを変更登録しました</p>';
    }
    
}

//====================================================
// 補助認証
//====================================================
$mobile = &Net_UserAgent_Mobile::singleton();

// EZ認証
if (!is_null($_SERVER['HTTP_X_UP_SUBNO'])) {
    if (file_exists($_conf['auth_ez_file'])) {
        $p_htm['auth_ctl'] = <<<EOP
EZ端末ID認証登録済[<a href="{$_SERVER['SCRIPT_NAME']}?ctl_regist_ez=1{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if ($_login->pass_x) {
            $p_htm['auth_ctl'] = <<<EOP
[<a href="{$_SERVER['SCRIPT_NAME']}?ctl_regist_ez=1&amp;regist_ez=1{$_conf['k_at_a']}">EZ端末IDで認証を登録</a>]<br>
EOP;
        }
    }

// J認証
} elseif ($mobile->isVodafone() && ($SN = $mobile->getSerialNumber()) !== NULL) {
    if (file_exists($_conf['auth_jp_file'])) {
        $p_htm['auth_ctl'] = <<<EOP
J端末ID認証登録済[<a href="{$_SERVER['SCRIPT_NAME']}?ctl_regist_jp=1{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if ($_login->pass_x) {
            $p_htm['auth_ctl'] = <<<EOP
[<a href="{$_SERVER['SCRIPT_NAME']}?ctl_regist_jp=1&amp;regist_jp=1{$_conf['k_at_a']}">J端末IDで認証を登録</a>]<br>
EOP;
        }
    }
    
// DoCoMo認証
} elseif ($mobile->isDoCoMo()) {
    if (file_exists($_conf['auth_docomo_file'])) {
        $p_htm['auth_ctl'] = <<<EOP
DoCoMo端末ID認証登録済[<a href="{$_SERVER['SCRIPT_NAME']}?ctl_regist_docomo=1{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if ($_login->pass_x) {
            $p_htm['auth_ctl'] = <<<EOP
[<a href="{$_SERVER['SCRIPT_NAME']}?ctl_regist_docomo=1&amp;regist_docomo=1{$_conf['k_at_a']}" utn>DoCoMo端末IDで認証を登録</a>]<br>
EOP;
        }
    }
    
// Cookie認証
} else {
    if ($_login->checkUserPwWithCid($_COOKIE['cid'])) {
            $p_htm['auth_cookie'] = <<<EOP
cookie認証登録済[<a href="cookie.php?ctl_regist_cookie=1{$_conf['k_at_a']}">解除</a>]<br>
EOP;
    } else {
        if ($_login->pass_x) {
            $p_htm['auth_cookie'] = <<<EOP
[<a href="cookie.php?ctl_regist_cookie=1&amp;regist_cookie=1{$_conf['k_at_a']}">cookieで認証を登録</a>]<br>
EOP;
        }
    }
}

//====================================================
// Cookie認証チェック
//====================================================
if (!empty($_REQUEST['check_regist_cookie'])) {

    if ($_login->checkUserPwWithCid($_COOKIE['cid'])) {
        if ($_REQUEST['regist_cookie'] == '1') {
            $_info_msg_ht .= '<p>○cookie認証登録完了</p>';
        } else {
            $_info_msg_ht .= '<p>×cookie認証解除失敗</p>';
        }
        
    } else {
        if ($_REQUEST['regist_cookie'] == '1') {
            $_info_msg_ht .= '<p>×cookie認証登録失敗</p>';
        } else  {
            $_info_msg_ht .= '<p>○cookie認証解除完了</p>';
        }
    }
}

//====================================================
// 認証ユーザ登録フォーム
//====================================================
$login_form_ht = <<<EOP
<form id="login_change" method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
    {$p_str['password']}の変更<br>
    {$_conf['k_input_ht']}
    新しい{$p_str['password']}: <input type="password" name="form_login_pass">
    <br>
    <input type="submit" name="submit" value="変更登録">
</form>\n
EOP;

if ($_conf['ktai']) {
    $login_form_ht = '<hr>'.$login_form_ht;
}

//=========================================================
// HTMLプリント
//=========================================================
$p_htm['body_onload'] = '';
if (empty($_conf['ktai'])) {
    $p_htm['body_onload'] = ' onLoad="setWinTitle();"';
}

P2Util::header_nocache();
P2Util::header_content_type();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$p_str['ptitle']}</title>
EOP;
if (empty($_conf['ktai'])) {
    @include("./style/style_css.inc");
    @include("./style/login_css.inc");
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js"></script>\n
EOP;
}
echo <<<EOP
</head>
<body{$p_htm['body_onload']}>
EOP;

if (empty($_conf['ktai'])) {
    echo <<<EOP
<p id="pan_menu"><a href="setting.php">ログイン管理</a> &gt; {$p_str['ptitle']}</p>
EOP;
}

// 情報表示
echo $_info_msg_ht;
$_info_msg_ht = "";
    
echo '<p id="login_status">';
echo <<<EOP
{$p_str['autho_user']}: {$_login->user_u}<br>
{$p_htm['auth_ctl']}
{$p_htm['auth_cookie']}
<br>
[<a href="./index.php?logout=1" target="_parent">{$p_str['logout']}する</a>]
EOP;
echo '</p>';

echo $login_form_ht;

if ($_conf['ktai']) {
    echo "<hr>\n";
    echo $_conf['k_to_index_ht'];
}

echo '</body></html>';

?>