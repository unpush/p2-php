<?php
// p2 -  殿堂入り関係の処理

require_once './p2util.class.php';
require_once './filectl.class.php';

//==================================================================
// 変数
//==================================================================

if ($_GET['setpal']=="0" or $_GET['setpal'] == "1") {
	$palbool = $_GET['setpal'];
}

//==================================================================
// key.idxに読み込む
//==================================================================

//idxfileのパスを求めて
$datdir_host = P2Util::datdirOfHost($_GET["host"]);
$idxfile = $datdir_host."/".$_GET['bbs']."/".$_GET['key'].".idx";

//データがあるなら読み込んで
if (is_readable($idxfile)) {
	$lines = @file($idxfile);
	$l = rtrim($lines[0]);
	$data = explode('<>', $l);
}

//==================================================================
// p2_palace.idxに書き込む
//==================================================================
$palace_idx = $_conf['pref_dir']. '/p2_palace.idx';

//================================================
// 読み込み
//================================================

// p2_palace ファイルがなければ生成
FileCtl::make_datafile($palace_idx, $_conf['palace_perm']);

//palace_idx読み込み;
$pallines = @file($palace_idx);

//================================================
// 処理
//================================================

// 最初に重複要素を削除
if ($pallines) {
	$i = -1;
	unset($neolines);
	foreach ($pallines as $pall) {
		$i++;
		$lar = explode('<>', $pall);
		if ($lar[1] == $_GET['key']) { // 重複回避
			$before_line_num = $i;
			continue;
		} elseif (!$lar[1]) { // keyのないものは不正データ
			continue;
		} else {
			$neolines[] = $pall;
		}
	}
}

// 新規データ設定
if ($_GET['setpal']) {
	$newdata="$data[0]<>{$_GET['key']}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>$data[9]<>{$_GET['host']}<>{$_GET['bbs']}\n";
}
	
if($_GET['setpal']==1 or $_GET['setpal']=="top"){
	$after_line_num=0;
	
}elseif($_GET['setpal']=="up"){
	$after_line_num=$before_line_num-1;
	if($after_line_num<0){$after_line_num=0;}
	
}elseif($_GET['setpal']=="down"){
	$after_line_num=$before_line_num+1;
	if( $after_line_num >= sizeof($neolines) ){$after_line_num="bottom";}
	
}elseif($_GET['setpal']=="bottom"){
	$after_line_num="bottom";
}

//================================================
//書き込む
//================================================
$fp = @fopen($palace_idx,"wb") or die("Error: $palace_idx を更新できませんでした");
@flock($fp, LOCK_EX);
if($neolines){
	$i=0;
	foreach($neolines as $l){
		if($i===$after_line_num){fputs($fp, $newdata);}
		fputs($fp, $l);
		$i++;
	}
	if($after_line_num==="bottom"){fputs($fp, $newdata);}
	//「$after_line_num=="bottom"」だと誤動作する。
}else{
	fputs($fp, $newdata);
}
@flock($fp, LOCK_UN);
fclose($fp);

?>