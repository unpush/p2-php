<?php
// p2 -  お気に入り編集

require_once("./conf.php");  // 基本設定
require_once("./filectl_class.inc");

authorize(); //ユーザ認証

//変数=============
$_info_msg_ht="";

//================================================================
//特殊な前置処理
//================================================================

//お気に板の追加・削除、並び替え
if( isset($_GET['setfavita']) or isset($_POST['setfavita']) ){
	include("./setfavita.inc");
}

//プリント用変数======================================================

// お気に板追加フォーム=================================================
$add_favita_form_ht = <<<EOFORM
<form method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
	<p>
		URL: <input type="text" id="url" name="url" value="http://" size="48">
		板名: <input type="text" id="itaj" name="itaj" value="" size="16">
		<input type="hidden" id="setfavita" name="setfavita" value="1">
		<input type="submit" name="submit" value="新規追加">
	</p>
</form>\n
EOFORM;

//================================================================
// ヘッダ
//================================================================
header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOP
<html lang="ja">
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>p2 - お気に板の並び替え</title>
EOP;

@include("./style/style_css.inc");
@include("./style/editfavita_css.inc");

echo <<<EOP
</head>
<body>
EOP;

echo $_info_msg_ht;
$_info_msg_ht="";

//================================================================
// メイン部分HTML表示
//================================================================

//================================================================
// お気に板
//================================================================

// favitaファイルがなければ生成
FileCtl::make_datafile($favita_path, $favita_perm);
// favita読み込み
$lines= file($favita_path);


echo <<<EOP
<div><b>お気に板の編集</b> [<a href="{$_SERVER['PHP_SELF']}" onClick='parent.menu.location.href="{$menu_php}?nr=1"'>メニューを更新</a>]</div>
EOP;

echo $add_favita_form_ht;

if($lines){
	echo "<table>";
	foreach($lines as $l){
		$l = rtrim($l);
		if( preg_match("/^\t?(.+)\t(.+)\t(.+)$/", $l, $matches) ){
			$itaj_en=base64_encode($matches[3]);
			$host=$matches[1];
			$bbs=$matches[2];
			$itaj_ht="&amp;itaj_en=".$itaj_en;
			echo <<<EOP
			<tr>
			<td><a href="{$_SERVER['PHP_SELF']}?host={$host}&bbs={$bbs}&setfavita=0" class="fav">★</a></td>
			<td><a href="{$subject_php}?host={$host}&bbs={$bbs}">{$matches[3]}</a></td>
			<td>[ <a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&bbs={$bbs}{$itaj_ht}&setfavita=top">▲</a></td>
			<td><a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&bbs={$bbs}{$itaj_ht}&setfavita=up">↑</a></td>
			<td><a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&bbs={$bbs}{$itaj_ht}&setfavita=down">↓</a></td>
			<td><a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&bbs={$bbs}{$itaj_ht}&setfavita=bottom">▼</a> ]</td>
			</tr>
EOP;
		}
	}
	echo "</table>";
}

//================================================================
// フッタHTML表示
//================================================================

echo <<<EOP
</body></html>
EOP;

?>