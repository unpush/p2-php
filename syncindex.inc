<?php
/*
	p2 -  お気にスレの同期
		最近読んだスレ、書き込み履歴、スレの殿堂
*/

require_once './brdctl.class.php';

//================================================
// 読み込み
//================================================
//favlistfileファイルがなければ終了
if (!file_exists($syncfile)) {
	return;
}

//favlistfile読み込み;
$lines = @file($syncfile);

//board読み込み
$_current = BrdCtl::read_brds();

//================================================
// 処理
//================================================

//板リストを単純配列に変換
$current = array();
foreach ($_current as $brdmenu) {
	foreach ($brdmenu->categories as $category) {
		foreach ($category->menuitas as $ita) {
			$current[] = "{$ita->host}<>{$ita->bbs}";
		}
	}
}

// ■データの同期
// 2ch/bbspinkの場合、板リストと現データをbbs（板名）で照合して、板リストデータで現データを上書きする。
$neolines = array();
foreach ($lines as $line) {
	$data = explode('<>', rtrim($line));
	if (preg_match('/^\w+\.(2ch\.net|bbspink\.com)$/', $data[10], $matches)) {
		$grep_pattern = '/^\w+\.' . preg_quote($matches[1], '/') . '<>' . preg_quote($data[11], '/') . '$/';
	} else {
		$neolines[] = $line;
		continue;
	}
	if ($findline = preg_grep($grep_pattern, $current)) {
		// $findlineは最初に見つかったものを利用。
		$newdata = explode('<>', rtrim(array_shift($findline)));
		$data[10] = $newdata[0];
		$data[11] = $newdata[1];
		$neolines[] = implode('<>', $data) . "\n";
	} else {
		$neolines[] = $line;
	}
}

//================================================
// 更新があれば、書き込む
//================================================
if (serialize($lines) != serialize($neolines)) {
	$fp = @fopen($syncfile, 'wb') or die("Error: $syncfile を更新できませんでした");
	@flock($fp, LOCK_EX);
	foreach ($neolines as $l) {
		fputs($fp, $l);
	}
	@flock($fp, LOCK_UN);
	fclose($fp);
	$sync_ok = true;
} else {
	$sync_ok = false;
}

?>