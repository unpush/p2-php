<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 -  殿堂入り関係の処理
*/

require_once (P2_LIBRARY_DIR . '/p2util.class.php');
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

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
	$idxfile = $datdir_host.'/'.$bbs.'/'.$key.'.idx';

	// 既にidxデータがあるなら読み込む
	if (is_readable($idxfile) && ($lines = @file($idxfile))) {
		$l = rtrim($lines[0]);
		$data = explode('<>', $l);
		$c = count($data);
		if ($c < 10) {
			while ($c < 10) {
				$data[] = '';
				$c++;
			};
		} elseif ($c > 10) {
			$data = array_slice($data, 0, 10);
		}
		unset($c);
	} else {
		$data = array_fill(0, 10, '');
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
	// 最初に重複要素を削除しておく
	if (!empty($pallines)) {
		$i = -1;
		$neolines = array();
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
		$newdata = $data;
		$newdata[1] = $key;
		$newdata[10] = $host;
		$newdata[11] = $bbs;
		$newline = implode('<>', $newdata) . "\n";
	}

	if ($setpal == 1 or $setpal == 'top') {
		$after_line_num = 0;	// 移動後の行番号

	} elseif ($setpal == 'up') {
		$after_line_num = $before_line_num-1;
		if ($after_line_num < 0) { $after_line_num = 0; }

	} elseif ($setpal == 'down') {
		$after_line_num = $before_line_num+1;
		if ($after_line_num >= sizeof($neolines)) { $after_line_num = 'bottom'; }

	} elseif ($setpal == 'bottom') {
		$after_line_num = 'bottom';

	} else {
		$after_line_num = null;
	}

	//================================================
	//書き込む
	//================================================
	$fp = @fopen($palace_idx, 'wb') or die("Error: {$palace_idx} を更新できませんでした");
	@flock($fp, LOCK_EX);
	if ($neolines) {
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
		//「$after_line_num == 'bottom'」だと誤動作する。
	} else {
		fputs($fp, $newline);
	}
	@flock($fp, LOCK_UN);
	fclose($fp);

	return true;
}

?>
