<?php
// p2 - スレッド情報ウィンドウ

require_once("./conf.php");  //基本設定ファイル
require_once("./thread_class.inc"); //スレッドクラス
require_once("./filectl_class.inc");
require_once("./datactl.inc"); //データ処理関数群
include_once("./dele.inc");

authorize(); //ユーザ認証

//================================================================
// 変数設定
//================================================================
$_info_msg_ht="";

$host = $_GET['host']; //"pc.2ch.net"
$bbs = $_GET['bbs']; //"php"
$key = $_GET['key']; //"1022999539"
$ttitle_en = $_GET['ttitle_en'];

//popup 0(false),1(true),2(true,クローズタイマー付)
if($_GET['popup']){$popup_ht="&amp;popup=1";}

//================================================================
//特殊な前置処理
//================================================================

//削除
if($_GET['dele'] && $key && $host && $bbs){
	include_once("dele.inc");
	$r = deleteLogs($host, $bbs, array($key));
	//echo $r;
	if(!$r){
		$title_msg="× ログ削除失敗";
		$info_msg="× ログ削除失敗";
	}elseif($r==1){
		$title_msg="○ ログ削除完了";
		$info_msg="○ ログ削除完了";
	}elseif($r==2){
		$title_msg="- ログはありませんでした";
		$info_msg="- ログはありませんでした";
	}
}

//履歴削除
if($_GET['offrec'] && $key && $host && $bbs){
	include_once("dele.inc");
	$r1 = offRecent($host, $bbs, $key);
	$r2 = offResHist($host, $bbs, $key);
	if( (!$r1) or (!$r2)){
		$title_msg="× 履歴解除失敗";
		$info_msg="× 履歴解除失敗";
	}elseif($r1==1 || $r2==1){
		$title_msg="○ 履歴解除完了";
		$info_msg="○ 履歴解除完了";
	}elseif($r1==2 && $r2==2){
		$title_msg="- 履歴にはありませんでした";
		$info_msg="- 履歴にはありませんでした";
	}
}

//お気に入りスレッド
elseif( isset($_GET['setfav']) && $key && $host && $bbs){
	include("setfav.inc");
}

//殿堂入り
elseif( isset($_GET['setpal']) && $key && $host && $bbs){
	include("setpalace.inc");
}

//スレッドあぼーん
elseif( isset($_GET['taborn']) && $key && $host && $bbs){
	include_once("settaborn.inc");
	settaborn($host, $bbs, $key, $_GET['taborn']);
}

//=================================================================
// メイン
//=================================================================

$aThread = new Thread;

$aThread->setThreadPathInfo($host, $bbs, $key); //hostを分解してidxファイルのパスを求める
$key_line = $aThread->getThreadInfoFromIdx($aThread->keyidx);

$aThread->itaj = getItaName($aThread->host, $aThread->bbs);
if(!$aThread->itaj){$aThread->itaj=$aThread->bbs;}

if(! $aThread->ttitle){
	if($ttitle_en){
		$aThread->ttitle=base64_decode($ttitle_en);
	}else{
		$aThread->setTitleFromLocal();
	}
}
if(! $ttitle_en){
	if($aThread->ttitle){$ttitle_en=base64_encode($aThread->ttitle);}
}
if($ttitle_en){$ttitle_en_ht="&amp;ttitle_en={$ttitle_en}";}

if($aThread->ttitle){
	$ttitle_name=$aThread->ttitle;
}else{
	$ttitle_name="スレッドタイトル未取得";
}

// favlist チェック =====================================
$favlines = @file($favlistfile); //お気にスレリスト 読込
if($favlines){
	foreach($favlines as $favaline){
		$favarray = explode("<>", $favaline);
		if($aThread->key==$favarray[1]){
			$aThread->fav = "1";
			if($favarray[0]) $aThread->ttitle = $favarray[0];
			break;
		}
	}
}

if($aThread->fav){$favmark="<span class=\"fav\">★</span>";}else{$favmark="<span class=\"fav\">+</span>";}
if($aThread->fav){$favdo=0;}else{$favdo=1;}

$fav_ht=<<<EOP
<a href="info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;setfav={$favdo}{$popup_ht}{$ttitle_en_ht}{$k_at_a}">{$favmark}</a>
EOP;

// palace チェック =========================================
$palace_idx = $prefdir. '/p2_palace.idx';

$pallines = @file($palace_idx); //殿堂入りスレリスト 読込
if($pallines){
	foreach($pallines as $palaline){
		$palarray = explode("<>", $palaline);
		if($aThread->key==$palarray[1]){
			$isPalace=true;
			if($palarray[0]){ $aThread->ttitle = $palarray[0]; }
			break;
		}
	}
}
if($isPalace){ $paldo=0; }else{$paldo=1;}
$pal_a_ht="info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;setpal={$paldo}{$popup_ht}{$ttitle_en_ht}{$k_at_a}";

if($isPalace){
	$pal_ht="<a href=\"{$pal_a_ht}\">★</a>";
}else{
	$pal_ht="<a href=\"{$pal_a_ht}\">+</a>";
}

//スレッドあぼーんチェック =====================================

//スレッドあぼーんリスト読込
$datdir_host = datdirOfHost($host);
$tabornlist = @file("{$datdir_host}/{$bbs}/p2_threads_aborn.idx");
if($tabornlist){
	foreach($tabornlist as $taline){
		$tarray = explode("<>", $taline);
		if($aThread->key==$tarray[1]){
			$isTaborn=true;
			break;
		}
	}
}

if($isTaborn){
	$tastr1="あぼーん中";
	$tastr2="あぼーん解除する";
}else{
	$tastr1="通常";
	$tastr2="あぼーんする";
}
if($isTaborn){$taborndo=0;}else{$taborndo=1;}

$taborn_ht=<<<EOP
{$tastr1} [<a href="info.php?host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}&amp;taborn={$taborndo}{$popup_ht}{$ttitle_en_ht}{$k_at_a}">{$tastr2}</a>]
EOP;


//ログありなしフラグセット===========
if( file_exists($aThread->keydat) or file_exists($aThread->keyidx)  ){$existLog=true;}

//=================================================================
// HTMLプリント
//=================================================================
if(!$ktai){
	$target_read_at = " target=\"read\"";
	$target_sb_at = " target=\"sbject\"";
}

$motothre_url = $aThread->getMotoThread($GLOBAL['ls']);
if ($title_msg) {
	$title_st = $title_msg;
} else {
	$title_st = "info - {$ttitle_name}";
}

header_nocache();
header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOHEADER
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$title_st}</title>\n
EOHEADER;

if(!$ktai){
	echo "<!-- ".$key_line." -->\n";
	@include("./style/style_css.inc"); //基本スタイルシート読込
	@include("./style/info_css.inc");
}

if($_GET['popup']==2){
	echo <<<EOSCRIPT
	<script type="text/javascript" src="js/closetimer.js"></script>
EOSCRIPT;
	$body_onload=<<<EOP
 onLoad="startTimer(document.getElementById('timerbutton'))"
EOP;
}

echo <<<EOP
</head>
<body{$body_onload}>
EOP;

echo $_info_msg_ht;
$_info_msg_ht="";

echo "<p>\n";
echo "<b><a class=\"thre_title\" href=\"{$_conf['read_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}{$k_at_a}\"{$target_read_at}>{$ttitle_name}</a></b>\n";
echo "</p>\n";

if($ktai){
	if($info_msg){
		echo "<p>".$info_msg."</p>\n";
	}
}

if(checkRecent($aThread->host, $aThread->bbs, $aThread->key) or checkResHist($aThread->host, $aThread->bbs, $aThread->key)){
	$offrec_ht=" / [<a href=\"info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;offrec=true{$popup_ht}{$ttitle_en_ht}{$k_at_a}\" title=\"このスレを「最近読んだスレ」と「書き込み履歴」から外します\">履歴から外す</a>]";
}

if(!$ktai){
	echo "<table cellspacing=\"0\">\n";
}
print_info_line("元スレ", "<a href=\"{$motothre_url}\"{$target_read_at}>{$motothre_url}</a>");
if(!$ktai){
	print_info_line("ホスト", $aThread->host);
}
print_info_line("板", "<a href=\"{$subject_php}?host={$aThread->host}&amp;bbs={$aThread->bbs}{$k_at_a}\"{$target_sb_at}>{$aThread->itaj}</a>");
if(!$ktai){
	print_info_line("key", $aThread->key);
}
if( $existLog ){
	print_info_line("ログ", "あり [<a href=\"info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;dele=true{$popup_ht}{$ttitle_en_ht}{$k_at_a}\">削除する</a>]{$offrec_ht}");
}else{
	print_info_line("ログ", "未取得{$offrec_ht}");
}
if($aThread->rnum){
	print_info_line("既得レス数", $aThread->rnum);
}elseif(!$aThread->rnum and $existLog ){
	print_info_line("既得レス数", "0");
}else{
	print_info_line("既得レス数", "-");
}
if($aThread->length){print_info_line("取得サイズ", $aThread->length);}

if(!$ktai){
	if(file_exists($aThread->keydat)){
		print_info_line("dat", $aThread->keydat);
	}else{
		print_info_line("dat", "-");
	}
	if(file_exists($aThread->keyidx)){
		print_info_line("idx", $aThread->keyidx);
	}else{
		print_info_line("idx", "-");
	}
}

print_info_line("お気にスレ", $fav_ht);
print_info_line("殿堂入り", $pal_ht);
print_info_line("表示", $taborn_ht);

if(!$ktai){
	echo "</table>\n";
}

if(!$ktai){
	if($info_msg){
		echo "<span class=\"infomsg\">".$info_msg."</span>\n";
	}else{
		echo "　\n";
	}
}

if($_GET['popup']){ //閉じるボタン
	echo '<div align="center">';
	if($_GET['popup']==1){
		echo '<form action=""><input type="button" value="ウィンドウを閉じる" onClick="window.close();"></form>';
	}elseif($_GET['popup']==2){
		echo <<<EOP
	<form action=""><input id="timerbutton" type="button" value="Close Timer" onClick="stopTimer(document.getElementById('timerbutton'))"></form>
EOP;
	}
	echo "</div>\n";
}

if($ktai){
	echo <<<EOP
<hr>
$k_to_index_ht
EOP;
}

echo <<<EOFOOTER
</body>
</html>
EOFOOTER;

//===============================================
// 関数
//===============================================
function print_info_line($s, $c){
	global $ktai;
	if($ktai){
		echo "{$s}: {$c}<br>";
	}else{
		echo "<tr><td class=\"tdleft\" nowrap><b>{$s}</b>&nbsp;</td><td class=\"tdcont\">{$c}</td></tr>\n";
	}
}

?>