<?php
// p2 - レス書き込みフォーム

require_once("./conf.php");  //基本設定ファイル読込
require_once("datactl.inc");
require_once './p2util.class.php';	// p2用のユーティリティクラス

authorize(); //ユーザ認証

//==================================================
// 変数
//==================================================
$_info_msg_ht="";

$fake_time = -10; // time を10分前に偽装
$time = time()-9*60*60;
$time = $time + $fake_time*60;

$bbs = $_GET['bbs'];
$key = $_GET['key'];
$host = $_GET['host'];

$rescount = $_GET['rc'];
$popup = $_GET['popup'];

$itaj = getItaName($host, $bbs);
if(!$itaj){$itaj=$bbs;}

$_GET['ttitle_en'] && $ttitle_en = $_GET['ttitle_en'];
if(! $ttitle){
	if($ttitle_en){ $ttitle=base64_decode($ttitle_en); }
}

if( P2Util::isHost2chs($host) and file_exists($sid2ch_php) ){ //2ch●書き込み
	$isMaruChar="●";
}else{
	$isMaruChar="";
}

if(!$ktai){
	$class_ttitle=" class=\"thre_title\"";
	$target_read=" target=\"read\"";
	$sub_size_at=" size=\"40\"";
	$name_size_at=" size=\"19\"";
	$mail_size_at=" size=\"19\"";
	$msg_cols_at=" cols=\"{$STYLE['post_msg_cols']}\"";
}else{
	$STYLE['post_msg_rows']=2;
}

if($_GET['newthread']){
	$ptitle="{$itaj} - 新規スレッド作成";
	if( P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host) ){ // machibbs、JBBS@したらば なら
		$submit_value="新規書き込み";
	}else{ //2ch
		$submit_value="新規スレッド作成";
	}
	$subject_ht=<<<EOP
<b><span{$class_ttitle}>タイトル</span></b>：<input type="text" name="subject"{$sub_size_at}><br>
EOP;
	if($ktai){$subject_ht="<a href=\"{$subject_php}?host={$host}&amp;bbs={$bbs}{$k_at_a}\">{$itaj}</a><br>".$subject_ht;}
	$newthread_hidden_ht="<input type=\"hidden\" name=\"newthread\" value=\"1\">";
}else{
	$ptitle="{$itaj} - レス書き込み";
	if( P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host) ){ // machibbs、JBBS@したらば なら
		$submit_value="書き込む";
	}else{ //2ch
		$submit_value="書き込む";
	}
	$ttitle_ht=<<<EOP
<p><b><a{$class_ttitle} href="{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}{$k_at_a}"{$target_read}>{$ttitle}</a></b></p>
EOP;
}

// key.idxから名前とメールを読込み
$datdir_host = datdirOfHost($host);
$key_idx = $datdir_host."/".$bbs."/".$key.".idx";
if (file_exists($key_idx)) {
	if ($lines=@file($key_idx)) {
		$line = explode("<>", $lines[0]);
		$FROM = $line[7];
		$mail = $line[8];
	}
}

// Safari補正用フォーム要素
if (P2Util::isBrowserSafariGroup()) {
	// mbstring有効時、Safari/KonquerorはUTF-8で投稿することで バックスラッシュとチルダが全角になるのを防ぐ【\~＼～】 
	if (extension_loaded('mbstring')) {
		$accept_charset_ht = ' accept-charset="UTF-8"';
		$safari_fix_ht = "";
	} else {
		$accept_charset_ht = "";
		$safari_fix_ht = <<<EOP
<br>
	Safari対策
	<input type="checkbox" name="fix_tilde" id="fix_tilde" value="1"><label for="fix_tilde">～→~</label>
	<input type="checkbox" name="fix_bslash" id="fix_bslash" value="1"><label for="fix_bslash">＼→\\</label>\n
EOP;
	}
} else {
	$accept_charset_ht = "";
	$safari_fix_ht = "";
}

//==========================================================
// HTMLプリント
//==========================================================
if (!$ktai) {
	$body_on_load=<<<EOP
 onLoad="setFocus('MESSAGE'); checkSage(document.getElementById('mail'));"
EOP;
	$on_check_sage=<<<EOP
onChange="checkSage(this);"
EOP;
	$sage_cb_ht=<<<EOP
<input id="sage" type="checkbox" onClick="mailSage(this);"><label for="sage">sage</label><br>
EOP;
}

header_content_type();
if ($doctype) { echo $doctype;}
echo <<<EOHEADER
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle}</title>
EOHEADER;
if(!$ktai){
	@include("style/style_css.inc"); //スタイルシート
	@include("style/post_css.inc"); //スタイルシート
echo <<<EOSCRIPT
	<script type="text/javascript" src="{$basic_js}"></script>
	<script type="text/javascript" src="js/post_form.js"></script>
EOSCRIPT;
}
echo <<<EOP
</head>
<body{$body_on_load}>
EOP;

echo $_info_msg_ht;
$_info_msg_ht="";

echo <<<EOP
{$ttitle_ht}
<form method="POST" action="./post.php"{$accept_charset_ht}>
	{$subject_ht}
	{$isMaruChar}名前： <input name="FROM" type="text" value="{$FROM}"{$name_size_at}> 
	 E-mail : <input id="mail" name="mail" type="text" value="{$mail}"{$mail_size_at}{$on_check_sage}>
	{$sage_cb_ht}
	<textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="off"></textarea>	
	<input type="submit" name="submit" value="{$submit_value}">
	{$safari_fix_ht}

	<input type="hidden" name="binyu" value="美乳">

	<input type="hidden" name="bbs" value="{$bbs}">
	<input type="hidden" name="key" value="{$key}">
	<input type="hidden" name="time" value="{$time}">
	
	<input type="hidden" name="host" value="{$host}">
	<input type="hidden" name="popup" value="{$popup}">
	<input type="hidden" name="rescount" value="{$rescount}">
	<input type="hidden" name="ttitle_en" value="{$ttitle_en}">
	{$newthread_hidden_ht}
	{$k_input_ht}
</form>
</body>
</html>
EOP;

?>