<?php
/*
	p2 - サブジェクト - フッタ表示
	for subject.php
*/

require_once './p2util.class.php';	// p2用のユーティリティクラス

$bbs_q = "&amp;bbs=".$aThreadList->bbs;
	
//dat倉庫=======================
if(!$aThreadList->spmode or $aThreadList->spmode=="taborn"){ //スペシャルモードでなければ、またはあぼーんリストなら
	$dat_soko_ht =<<<EOP
	<a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=soko" target="_self">dat倉庫</a> | 
EOP;
}

//あぼーん中のスレッド=================
if($ta_num){
	$taborn_link_ht=<<<EOP
	<a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=taborn" target="_self">あぼーん中のスレッド ({$ta_num})</a> | 
EOP;
}

//新規スレッド作成=======
if(!$aThreadList->spmode){
	$buildnewthread_ht =<<<EOP
	<a href="post_form.php?host={$aThreadList->host}{$bbs_q}&amp;newthread=true" target="_self" onClick="return OpenSubWin('post_form.php?host={$aThreadList->host}{$bbs_q}&amp;newthread=true&amp;popup=1',{$STYLE['post_pop_size']},0,0)">新規スレッド作成</a>
EOP;
}

// HTMLプリント==============================================

echo "</table>\n";

// チェックフォーム =====================================
echo $check_form_ht;
//フォームフッタ
echo <<<EOP
		<input type="hidden" name="host" value="{$aThreadList->host}">
		<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
		<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
	</form>\n
EOP;
	
// sbject ツールバー =====================================
include("./sb_toolbar.inc");

echo "<p>";
echo $dat_soko_ht;
echo $taborn_link_ht;
echo $buildnewthread_ht;
echo "</p>";

// スペシャルモードでなければフォーム入力補完========================
if (!$aThreadList->spmode) {
	if (P2Util::isHostJbbsShitaraba($aThread->host)) { // したらば
		$ini_url_text = "http://{$aThreadList->host}/bbs/read.cgi?BBS={$aThreadList->bbs}&KEY=";
	} elseif (P2Util::isHostMachiBbs($aThreadList->host)) { // まちBBS
		$ini_url_text = "http://{$aThreadList->host}/bbs/read.pl?BBS={$aThreadList->bbs}&KEY=";
	} elseif (P2Util::isHostMachiBbsNet($aThreadList->host)) { // まちビねっと
		$ini_url_text = "http://{$aThreadList->host}/test/read.cgi?bbs={$aThreadList->bbs}&key=";
	} else {
		$ini_url_text = "http://{$aThreadList->host}/test/read.cgi/{$aThreadList->bbs}/";
	}
}

//if(!$aThreadList->spmode || $aThreadList->spmode=="fav" || $aThreadList->spmode=="recent" || $aThreadList->spmode=="res_hist"){	
$onClick_ht =<<<EOP
var url_v=document.forms["urlform"].elements["url_text"].value;
if (url_v=="" || url_v=="{$ini_url_text}") {
	alert("見たいスレッドのURLを入力して下さい。 例：http://pc.2ch.net/test/read.cgi/mac/1034199997/");
	return false;
}
EOP;
echo <<<EOP
	<form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
			スレURLを直接指定
			<input id="url_text" type="text" value="{$ini_url_text}" name="nama_url" size="54">
			<input type="submit" name="btnG" value="表示" onClick='{$onClick_ht}'>
	</form>\n
EOP;
//}

//================
echo <<<EOP
</body>
</html>
EOP;

?>