<?php
// p2 - 2ch●ログイン管理

include_once './conf.inc.php';  // 基本設定
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './filectl.class.php';

authorize(); // ユーザ認証

//================================================================
// 変数
//================================================================

if (isset($_POST['login2chID'])) { $login2chID = $_POST['login2chID']; }
if (isset($_POST['login2chPW'])) { $login2chPW = $_POST['login2chPW']; }
if (isset($_POST['autoLogin2ch'])) { $autoLogin2ch = $_POST['autoLogin2ch']; }

$_info_msg_ht = "";

//==============================================================
// IDとPWを登録保存
//==============================================================
if (isset($_POST['login2chID']) && isset($_POST['login2chPW'])) {

	if (isset($_POST['autoLogin2ch'])) {
		$autoLogin2ch = $_POST['autoLogin2ch'];
	} else {
		$autoLogin2ch = 0;
	}

	P2Util::saveIdPw2ch($_POST['login2chID'], $_POST['login2chPW'], $autoLogin2ch);

	include_once './login2ch.inc.php';
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
		include_once './login2ch.inc.php';
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

$body_onload = "";
if (!$_conf['ktai']) {
	$body_onload = " onLoad=\"setWinTitle();\"";
}

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
	@include("./style/login2ch_css.inc");
	echo <<<EOP
	<script type="text/javascript" src="js/basic.js"></script>
EOP;
}

echo <<<EOP
	<script type="text/javascript">
	<!--
	function checkPass2ch(){ 
		pass2ch_input = document.getElementById('login2chPW');
		if(pass2ch_input.value==""){
			alert("パスワードを入力して下さい");
			return false;
		}
	}
	// -->
	</script>
</head>
<body{$body_onload}>
EOP;

if(!$_conf['ktai']){
	echo <<<EOP
<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
EOP;
}

echo $_info_msg_ht;
$_info_msg_ht = "";

//================================================================
// 2ch●ログインフォーム
//================================================================

if (file_exists($_conf['sid2ch_php'])) {
	$idsub_str = "再{$login_st}する";
	$form_now_log = <<<EOFORM
	<form id="form_logout" method="GET" action="{$_SERVER['PHP_SELF']}" target="_self">
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
	<form id="form_logout" method="GET" action="{$_SERVER['PHP_SELF']}" target="_self">
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

//プリント=================================
echo "<div id=\"login_status\">";
echo $form_now_log;
echo "</div>";

if ($_conf['ktai']) {
	echo "<hr>";
}

echo <<<EOFORM
<form id="login_with_id" method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
	{$_conf['k_input_ht']}
	ID: <input type="text" name="login2chID" value="{$login2chID}"{$id_input_size_at}><br>
	{$password_st}: <input type="password" name="login2chPW" id="login2chPW"{$pass_input_size_at}><br>
	<input type="checkbox" id="autoLogin2ch" name="autoLogin2ch" value="1"{$autoLogin2ch_checked}><label for="autoLogin2ch">起動時に自動{$login_st}する</label><br>
	<input type="submit" name="submit" value="{$idsub_str}" onClick="return checkPass2ch();">
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
	echo "<hr>";
	echo $k_to_index_ht;
}

echo <<<EOP
</body>
</html>
EOP;

?>