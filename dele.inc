<?php
// p2 - スレッドデータ、DATを削除する

require_once("datactl.inc");

// ==================================================
// 指定した配列keysのログを削除する関数
// ==================================================
function deleteLogs($host, $bbs, $keys){
	
	$datdir_host=datdirOfHost($host);
	
	if(sizeof($keys)==1){ //指定keyのログを削除
		offRecent($host, $bbs, $keys[0]);
		offResHist($host, $bbs, $keys[0]);
		$r = deleteThisKey($datdir_host, $bbs, $keys[0]);
		
	}elseif($keys){ //指定key配列のログを削除（taborn）
		foreach($keys as $keyval){
			offRecent($host, $bbs, $keyval);
			offResHist($host, $bbs, $keyval);
			deleteThisKey($datdir_host, $bbs, $keyval);
		}
		$r = true;
	}

	return $r;
}//最適化の余地あり

// ==================================================
// 指定したキーのスレッドログを削除する関数
// ==================================================
function deleteThisKey($datdir_host, $bbs, $key){

	//$datdir_host=datdirOfHost($host);
	$anidx = "$datdir_host/{$bbs}/{$key}.idx";
	$adat = "$datdir_host/{$bbs}/{$key}.dat";
	
	//File処理
	if( file_exists($anidx) ){
		$done=true;
		unlink($anidx);
	}
	if( file_exists($adat) ){
		$done=true;
		unlink($adat);
	}
	
	if($done){
		return 1;
	}else{
		return 2;
	}
}


/**
 * 指定したキーが最近読んだスレに入ってるかどうかをチェックする
 */
function checkRecent($host, $bbs, $key)
{
	global $rctfile;

	$lines = @file($rctfile); // 読み込み
	if ($lines) { // あればtrue
		foreach ($lines as $l) {
			$l = rtrim($l);
			$lar = explode('<>', $l);
			if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) { // あったら
				return true;
			}
		}
	}
	return false;
}

/**
 * 指定したキーが書き込み履歴に入ってるかどうかをチェックする
 */
function checkResHist($host, $bbs, $key)
{
	global $prefdir;
	
	$rh_idx = $prefdir."/p2_res_hist.idx";
	$lines = @file($rh_idx); // 読み込み
	if ($lines) {	// あればtrue
		foreach ($lines as $l) {
			$l = rtrim($l);
			$lar = explode('<>', $l);
			if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) { // あったら
				return true;
			}
		}
	}
	return false;
}

/**
 * 指定したキーの履歴を削除する
 */
function offRecent($host, $bbs, $key)
{
	global $rctfile;

	$lines = @file($rctfile); // 読み込み
	if ($lines) { // あれば削除
		foreach ($lines as $line) {
			$line = rtrim($line);
			$lar = explode('<>', $line);
			if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) { // 削除
				$done = true;
				continue;
			}
			$neolines[] = $line;
		}
	}

	//書き込む
	$fp = @fopen($rctfile, "wb") or die("Error: cannot write. ({$rctfile})");
	if ($neolines) {
		foreach($neolines as $l){
			fputs($fp, $l."\n");
		}
	}
	fclose($fp);
	
	if ($done) {
		return 1;
	} else {
		return 2;
	}
}

/**
 * 指定したキーの書き込み履歴を削除する
 */
function offResHist($host, $bbs, $key)
{
	global $prefdir;
	
	$rh_idx = $prefdir."/p2_res_hist.idx";
	$lines = @file($rh_idx); // 読み込み
	if ($lines) {	// あれば削除
		foreach($lines as $l){
			$l = rtrim($l);
			$lar = explode('<>', $l);
			if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) { // 削除
				$done = true;
				continue;
			}
			$neolines[] = $l;
		}
	}

	// 書き込む
	$fp = @fopen($rh_idx,"wb") or die("Error: cannot write. ({$rh_idx})");
	if ($neolines) {
		foreach ($neolines as $l) {
			fputs($fp, $l."\n");
		}
	}
	fclose($fp);
	
	if ($done) {
		return 1;
	} else {
		return 2;
	}
}

?>