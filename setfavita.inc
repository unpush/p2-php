<?php
// p2 -  お気に板の処理

require_once("./filectl_class.inc");

//===============================================
// 変数
//===============================================

if(! isset($setfavita) ){$setfavita = $_GET['setfavita'];}
if(! isset($setfavita) ){$setfavita = $_POST['setfavita'];}

$host = $_GET['host'];
$bbs = $_GET['bbs'];
if($_POST['url']){
	if( preg_match("/http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?/", $_POST['url'], $matches) ){
		$host = $matches[1];
		$bbs = $matches[2];
	}else{
		$_info_msg_ht .= "<p>p2 info: 「{$_POST['url']}」は板のURLとして無効です。</p>";
	}
}

if($_POST['itaj']){
	//magic_quates 除去
	if(get_magic_quotes_gpc()) {
		$itaj = stripslashes($_POST['itaj']);
	} else { 
		$itaj = $_POST['itaj']; 
	} 
}
if(! isset($itaj) && isset($_GET['itaj_en']) ){
	$itaj = base64_decode($_GET['itaj_en']);
} 
if( empty($itaj) ){$itaj = $bbs;} 

//================================================
// 読み込み
//================================================
//favita_pathファイルがなければ生成
FileCtl::make_datafile($favita_path, $favita_perm);

//favita_path読み込み;
$lines= @file($favita_path);

//================================================
// 処理
//================================================

//最初に重複要素を消去
if($lines){
	$i=-1;
	unset($neolines);
	foreach($lines as $l){
		$i++;
		
		/* 旧データ（ver0.6.0以下）移行措置 */
		if(! preg_match("/^\t/", $l)){ $l="\t".$l;}
		/*------------------------*/
		
		$lar = explode("\t", $l);
		
		if( $lar[1]==$host and $lar[2]==$bbs ){ //重複回避
			$before_line_num=$i;
			continue;
		//}elseif(!$lar[3]){//不正データ（板名なし）もアウト
		//	continue;
		}else{
			$neolines[]=$l;
		}
	}
}

//新規データ設定
if($setfavita){
	if($host && $bbs && $itaj){
		$newdata="\t{$host}\t{$bbs}\t{$itaj}\n";
	}
}
	
if($setfavita==1 or $setfavita=="top"){
	$after_line_num=0;

}elseif($setfavita=="up"){
	$after_line_num=$before_line_num-1;
	if($after_line_num<0){$after_line_num=0;}
	
}elseif($setfavita=="down"){
	$after_line_num=$before_line_num+1;
	if( $after_line_num >= sizeof($neolines) ){$after_line_num="bottom";}
	
}elseif($setfavita=="bottom"){
	$after_line_num="bottom";
}

//================================================
//書き込む
//================================================
$fp = @fopen($favita_path,"wb") or die("Error: $favita_path を更新できませんでした");
if($neolines){
	$i=0;
	foreach($neolines as $l){
		if($i===$after_line_num){fputs($fp, $newdata);}
		fputs($fp, $l);
		$i++;
	}
	if($after_line_num==="bottom"){	fputs($fp, $newdata); }
	//「$after_line_num=="bottom"」だと誤動作する。
}else{
	fputs($fp, $newdata);
}
fclose($fp);

?>