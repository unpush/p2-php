<?php
/*
	p2 ログイン

	最新更新日: 2004/10/24
*/

require_once("./conf.php");  //基本設定
require_once("./filectl_class.inc");
require_once("./login.inc");

authorize(); //ユーザ認証

$_info_msg_ht="";

if(!$login['use']){
	die("p2 info: 現在、ユーザ認証は「利用しない」設定になっています。<br>この機能を管理するためには、まず conf.php で設定を有効にして下さい。");
}

//=========================================================
// 前置処理
//=========================================================
regist_set_ktai($auth_ez_file, $auth_jp_file);
regist_set_cookie();

//=========================================================
// 書き出し用変数
//=========================================================
$ptitle="p2認証ユーザ管理";

$autho_user_ht="";
$auth_ctl_ht="";
$auth_sub_input_ht="";
$ivalue_user="";

if($ktai){
	$status_st="ｽﾃｰﾀｽ";
	$autho_user_st="認証ﾕｰｻﾞ";
	$client_host_st="端末ﾎｽﾄ";
	$client_ip_st="端末IPｱﾄﾞﾚｽ";
	$browser_ua_st="ﾌﾞﾗｳｻﾞUA";
	$p2error_st="p2 ｴﾗｰ";
	
	$user_st="ﾕｰｻﾞ";
	$password_st="ﾊﾟｽﾜｰﾄﾞ";
}else{
	$status_st="ステータス";
	$autho_user_st="認証ユーザ";
	$client_host_st="端末ホスト";
	$client_ip_st="端末IPアドレス";
	$browser_ua_st="ブラウザUA";
	$p2error_st="p2 エラー";
	
	$user_st="ユーザ";
	$password_st="パスワード";
}


if($login['use']){
	$autho_user_ht="{$autho_user_st}: {$login['user']}<br>";
}

//補助認証=====================================
//EZ認証===============
if($_SERVER['HTTP_X_UP_SUBNO']){
	if( file_exists($auth_ez_file) ){
		$auth_ctl_ht=<<<EOP
EZ端末ID認証登録済[<a href="{$_SERVER['PHP_SELF']}?regist_ez=out{$k_at_a}">解除</a>]<br>
EOP;
	}else{
		if($_SERVER['PHP_AUTH_USER']){
			$auth_ctl_ht=<<<EOP
[<a href="{$_SERVER['PHP_SELF']}?regist_ez=in{$k_at_a}">EZ端末IDで認証を登録</a>]<br>
EOP;
		}
		$auth_sub_input_ht=<<<EOP
	<input type="checkbox" name="regist_ez" value="in" checked>EZ端末IDで認証を登録<br>
EOP;
	}

// J認証 ================
} elseif (preg_match('{(J-PHONE|Vodafone)/([^/]+?/)+?SN(.+?) }', $_SERVER['HTTP_USER_AGENT'], $matches)) {
	if (file_exists($auth_jp_file)) {
		$auth_ctl_ht=<<<EOP
J端末ID認証登録済[<a href="{$_SERVER['PHP_SELF']}?regist_jp=out{$k_at_a}">解除</a>]<br>
EOP;
	} else {
		if ($_SERVER['PHP_AUTH_USER']) {
			$auth_ctl_ht = <<<EOP
[<a href="{$_SERVER['PHP_SELF']}?regist_jp=in{$k_at_a}">J端末IDで認証を登録</a>]<br>
EOP;
		}
		$auth_sub_input_ht = <<<EOP
	<input type="checkbox" name="regist_jp" value="in" checked>J端末IDで認証を登録<br>
EOP;
	}
	
//Cookie認証================
}else{
	if( ($_COOKIE["p2_user"]==$login['user']) && ($_COOKIE["p2_pass"] == $login['pass'])){
			$auth_cookie_ht = <<<EOP
cookie認証登録済[<a href="cookie.php?regist_cookie=out{$k_at_a}">解除</a>]<br>
EOP;
	}else{
		if($_SERVER['PHP_AUTH_USER']){
			$auth_cookie_ht = <<<EOP
[<a href="cookie.php?regist_cookie=in{$k_at_a}">cookieで認証を登録</a>]<br>
EOP;
		}
	}
	$auth_sub_input_ht = <<<EOP
	<input type="checkbox" name="regist_cookie" value="in" checked>cookieに保存する<br>
EOP;
}

// Cookie認証チェック ====================================
if ($_GET['regist_cookie_check']) {
	if (($_COOKIE["p2_user"] == $login['user']) && ($_COOKIE["p2_pass"] == $login['pass'])) {
		if($_GET['regist_cookie_check']=="in"){
			$_info_msg_ht .= "<p>○cookie認証登録完了</p>";
		}elseif($_GET['regist_cookie_check']=="out"){
			$_info_msg_ht .= "<p>×cookie認証解除失敗</p>";
		}
	}else{
		if($_GET['regist_cookie_check']=="out"){
			$_info_msg_ht .= "<p>○cookie認証解除完了</p>";
		}elseif($_GET['regist_cookie_check']=="in"){
			$_info_msg_ht .= "<p>×cookie認証登録失敗</p>";
		}
	}
}


// 認証ユーザ設定読み込み========
if( file_exists($auth_user_file) ){
	include($auth_user_file);	
	if( isset($login['user']) ){
		$ivalue_user=$login['user'];
	}
}
if( isset($_POST['login_user']) ){
	$ivalue_user=$_POST['login_user'];
}
	
// 認証ユーザ登録フォーム================
$login_form_ht =<<<EOP
<form id="login_change" method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
	認証{$user_st}名と{$password_st}の変更<br>
	{$k_input_ht}
	{$user_st}: <input type="text" name="login_user" value="{$ivalue_user}"><br>
	{$password_st}: <input type="password" name="login_pass"><br>
	{$auth_sub_input_ht}
	<br>
	<input type="submit" name="submit" value="変更登録">
</form>\n
EOP;


//ユーザ登録処理=================================
if ($_POST['login_user'] && $_POST['login_pass']) {

	if( isStrInvalid($_POST['login_user']) || isStrInvalid($_POST['login_pass']) ){
		$_info_msg_ht.="<p>p2 error: {$user_st}名と{$password_st}は半角英数字で入力して下さい。</p>";

	}else{
		$crypted_login_pass = crypt($_POST['login_pass'], $_POST['login_pass']);
		$auth_user_cont =<<<EOP
<?php
\$login['user'] = '{$_POST["login_user"]}';
\$login['pass'] = '{$crypted_login_pass}';
?>
EOP;
		FileCtl::make_datafile($auth_user_file, $pass_perm); //$auth_user_file がなければ生成
		$fp = @fopen($auth_user_file,"w") or die("p2 Error: $auth_user_file を保存できませんでした。認証ユーザ登録失敗。");
		fputs($fp, $auth_user_cont);
		fclose($fp);
		
		$_info_msg_ht.="<p>○認証{$user_st}「{$_POST['login_user']}」を登録しました</p>";
	}
	
}else{
	
	if($_POST['login_user'] || $_POST['login_pass']){
		if(!$_POST['login_user']){
			$_info_msg_ht.="<p>p2 error: {$user_st}名が入力されていません。</p>";
		}elseif(!$_POST['login_pass']){
			$_info_msg_ht.="<p>p2 error: {$password_st}が入力されていません。</p>";
		}
	}
	
}

$body_onload="";
if(!$ktai){
	$body_onload=" onLoad=\"setWinTitle();\"";
}

//=========================================================
// HTMLプリント
//=========================================================
header_nocache();
header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle}</title>
EOP;
if(!$ktai){
	@include("./style/style_css.inc");
	@include("./style/login_css.inc");
	echo <<<EOP
	<script type="text/javascript" src="{$basic_js}"></script>
EOP;
}
echo <<<EOP
</head>
<body{$body_onload}>
EOP;

if(!$ktai){
	echo <<<EOP
<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
EOP;
}

echo $_info_msg_ht;
$_info_msg_ht="";
	
echo "<p id=\"login_status\">";
echo <<<EOP
{$autho_user_ht}
{$auth_ctl_ht}
{$auth_cookie_ht}
EOP;
echo "</p>";

if($ktai){
	echo "<hr>";
}

echo $login_form_ht;

if($ktai){
	echo "<hr>\n";
	echo $k_to_index_ht;
}

echo <<<EOP
</body>
</html>
EOP;

?>
