<?php
/*
	p2 -  殿堂入り関係の処理
*/

require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './filectl.class.php';

/**
 * スレを殿堂入りにセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setPal($host, $bbs, $key, $setpal)
{
	global $_conf;

	//==================================================================
	// key.idx を読み込む
	//==================================================================
	// idxfileのパスを求めて
	$datdir_host = P2Util::datdirOfHost($host);
	$idxfile = $datdir_host."/".$bbs."/".$key.".idx";

	// 既にidxデータがあるなら読み込む
	if ($lines = @file($idxfile)) {
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

	// palace_idx 読み込み
	$pallines = @file($palace_idx);

	//================================================
	// 処理
	//================================================
	// 最初に重複要素を削除しておく
	if (!empty($pallines)) {
		$i = -1;
		unset($neolines);
		foreach ($pallines as $l) {
			$i++;
			$l = rtrim($l);
			$lar = explode('<>', $l);
			// 重複回避
			if ($lar[1] == $key) {
				$before_line_num = $i;	// 移動前の行番号をセット
				continue;
			// keyのないものは不正データなのでスキップ
			} elseif (!$lar[1]) {
				continue;
			} else {
				$neolines[] = $l;
			}
		}
	}

	// 新規データ設定
	if ($setpal) {
		$newline = "$data[0]<>{$key}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>$data[9]<>{$host}<>{$bbs}"."\n";
	}
	
	if ($setpal == 1 or $setpal == "top") {
		$after_line_num = 0;	// 移動後の行番号
	
	} elseif ($setpal == "up") {
		$after_line_num = $before_line_num - 1;
		if ($after_line_num < 0) { $after_line_num = 0; }
	
	} elseif ($setpal == "down") {
		$after_line_num = $before_line_num + 1;
		if ($after_line_num >= sizeof($neolines)) { $after_line_num = "bottom"; }
	
	} elseif ($setpal == "bottom") {
		$after_line_num = "bottom";
	}

	//================================================
	// 書き込む
	//================================================
	$fp = @fopen($palace_idx, 'wb') or die("Error: $palace_idx を更新できませんでした");
	@flock($fp, LOCK_EX);
	if (!empty($neolines)) {
		$i = 0;
		foreach ($neolines as $l) {
			if ($i === $after_line_num) {
				fputs($fp, $newline);
			}
			fputs($fp, $l."\n");
			$i++;
		}
		if ($after_line_num === 'bottom') {
			fputs($fp, $newline);
		}
		//「$after_line_num == "bottom"」だと誤動作するよ。
	} else {
		fputs($fp, $newline);
	}
	@flock($fp, LOCK_UN);
	fclose($fp);
	
	return true;
}
?>
