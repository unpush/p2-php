<?php
// p2 - 書き込み履歴 レス内容表示
// フレーム分割画面、右下部分

require_once("./conf.php"); //基本設定読込
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once("datactl.inc");
require_once("res_hist_class.inc");
require_once("read_res_hist.inc");

//$debug=true;
$debug && include_once("./profiler.inc"); //
$debug && $prof = new Profiler( true ); //

authorize(); //ユーザ認証

//======================================================================
// 変数
//======================================================================
$newtime = date("gis");
$p2_res_hist_dat_php = $prefdir."/p2_res_hist.dat.php";

$_info_msg_ht = "";
$deletemsg_st = "削除";
$ptitle = "書き込んだレスの記録";

//================================================================
//特殊な前置処理
//================================================================
//削除
if ($_POST['submit'] == $deletemsg_st) {
	deleMsg($_POST['checked_hists']);
}

// 旧形式の書き込み履歴を新形式に変換する
P2Util::transResHistLog();

//======================================================================
// メイン
//======================================================================

//==================================================================
// 特殊DAT読み
//==================================================================
// 読み込んで
if (!$datlines = P2Util::fileDataPhp($p2_res_hist_dat_php)) {
	die("p2 - 書き込み履歴内容は空っぽのようです");
}

$aResHist = new ResHist;

$n = 1;
if ($datlines) {
	foreach ($datlines as $aline) {

		// &<>/ → &xxx; のエスケープを元に戻す
		$aline = P2Util::unescapeDataPhp($aline);

		$aResArticle = new ResArticle;
		
		$resar = explode("\t", $aline);
		$aResArticle->name = $resar[0];
		$aResArticle->mail = $resar[1];
		$aResArticle->daytime = $resar[2];
		$aResArticle->msg = $resar[3];
		$aResArticle->ttitle = $resar[4];
		$aResArticle->host = $resar[5];
		$aResArticle->bbs = $resar[6];
		$aResArticle->itaj = getItaName($aResArticle->host, $aResArticle->bbs);
		if (!$aResArticle->itaj) {$aResArticle->itaj = $aResArticle->bbs;}
		$aResArticle->key = trim($resar[7]);
		$aResArticle->order = $n;
		
		$aResHist->addRes($aResArticle);
		
		$n++;
	}
}

//==================================================================
// ヘッダ 表示
//==================================================================
header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle}</title>
EOP;

if(!$_conf['ktai']){
	@include("style/style_css.inc"); //スタイルシート
	@include("style/read_css.inc"); //スタイルシート

	echo <<<EOSCRIPT
	<script type="text/javascript" src="{$basic_js}"></script>
	<script type="text/javascript" src="{$respopup_js}"></script>
EOSCRIPT;
}

echo <<<EOP
</head>
<body>
EOP;

echo $_info_msg_ht;
$_info_msg_ht="";

if($_conf['ktai']){
	echo "{$ptitle}<br>";
	echo "<div {$pointer_at}=\"header\">";
	$aResHist->showNaviK("header");
	echo " <a {$accesskey}=\"8\" href=\"#footer\"{$k_at_a}>8.▼</a><br>";
	echo "</div>";
	echo "<hr>";
	
}else{
	echo <<<EOP
<form method="POST" action="./read_res_hist.php#footer" target="_self">
EOP;

	echo <<<EOP
<table id="header" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td align="left">
			&nbsp;
		</td>
		<td align="right"><a href="#footer">▼</a></td>
	</tr>
</table>\n
EOP;

	echo <<<EOP
<table id="header" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td align="left">
			<h3 class="thread_title">{$ptitle}</h3>
		</td>
		<td align="right">&nbsp;
		</td>
	</tr>
</table>\n
EOP;
}


//==================================================================
// レス記事 表示
//==================================================================
if($_conf['ktai']){
	$aResHist->showArticlesK();
}else{
	$aResHist->showArticles();
}

//==================================================================
// フッタ 表示
//==================================================================
if($_conf['ktai']){
	echo "<div {$pointer_at}=\"footer\">";
	$aResHist->showNaviK("footer");
	echo " <a {$accesskey}=\"2\" href=\"#header\"{$k_at_a}>2.▲</a><br>";
	echo "</div>";
	echo "<p>{$k_to_index_ht}</p>";
}else{
	echo "<hr>";
	echo <<<EOP
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td align="left">
			チェックした項目を<input type="submit" name="submit" value="{$deletemsg_st}">
		</td>
		<td align="right"><a href="#header">▲</a></td>
	</tr>
</table>\n
EOP;
}

$debug && $prof->printTimers( true );//

if(!$_conf['ktai']){
	echo <<<EOP
	</form>
EOP;
}

echo <<<EOFOOTER
</body>
</html>
EOFOOTER;

?>