<?php
// p2 - スレッドあぼーんの関数

require_once("./filectl_class.inc");
require_once("./datactl.inc");

//====================================================================
// settaborn - スレッドあぼーんをオンオフする関数
// $set は、0(解除), 1(追加), 2(トグル)
//====================================================================
function settaborn($host, $bbs, $key, $set){
	global $title_msg, $info_msg, $p2_perm;
	
	//==================================================================
	// key.idx 読み込む
	//==================================================================
	
	//idxfileのパスを求めて
	$datdir_host=datdirOfHost($host);
	$idxfile="{$datdir_host}/{$bbs}/{$key}.idx";
	
	//データがあるなら読み込む
	if( is_readable($idxfile) ){
		$lines=@file($idxfile);
		$data = explode("<>", $lines[0]);
	}
	
	//==================================================================
	// p2_threads_aborn.idxに書き込む
	//==================================================================
	
	// p2_threads_aborn.idx のパス取得
	$datdir_host = datdirOfHost($host);
	$taborn_idx = "{$datdir_host}/{$bbs}/p2_threads_aborn.idx";
	
	// p2_threads_aborn.idx がなければ生成
	FileCtl::make_datafile($taborn_idx, $p2_perm);
	
	// p2_threads_aborn.idx 読み込み;
	$taborn_lines= @file($taborn_idx);
	
	//最初に重複要素を削除
	if($taborn_lines){
		foreach($taborn_lines as $tal){
			$lar = explode("<>", $tal);
			if($lar[1]==$key){
				$aborn_attayo=true; //既にあぼーん中である
				if($set==0 or $set==2){
					$title_msg_pre="+ あぼーん 解除しますた";
					$info_msg_pre="+ あぼーん 解除しますた";
				}
				continue;
			} //重複回避
			if(! $lar[1]){continue;} //keyのないものは不正データ
			$neolines[]=$tal;
		}
	}
	
	//新規データ追加
	if($set==1 or !$aborn_attayo && $set==2){
		$newdata="$data[0]<>{$key}<><><><><><><><>\n";
		$neolines ? array_unshift($neolines, $newdata) : $neolines=array($newdata);
		$title_msg_pre="○ あぼーん しますた";
		$info_msg_pre="○ あぼーん しますた";
	}
	
	//書き込む
	$fp = @fopen($taborn_idx,"w") or die("Error: $taborn_idx を更新できませんでした");
	if($neolines){
		foreach($neolines as $l){ fputs($fp,"$l"); }
	}
	fclose($fp);
	
	$title_msg=$title_msg_pre;
	$info_msg=$info_msg_pre;
	return true;
}

?>