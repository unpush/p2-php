<?php
// p2 -  お気に板の同期

require_once("./brdctl_class.inc");

//================================================
// 読み込み
//================================================
//favita_pathファイルがなければ終了
if (!file_exists($favita_path)) {
	return;
}

//favita_path読み込み;
$lines = @file($favita_path);

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
			$current[] = "\t{$ita->host}\t{$ita->bbs}\t{$ita->itaj}\n";
		}
	}
}

//データの同期
$neolines = array();
foreach ($lines as $line) {
	$data = explode("\t", rtrim($line));
	if (preg_match('/^\w+\.(2ch\.net|bbspink\.com)$/', $data[1], $matches)) {
		$grep_pattern = '/^\t\w+\.' . preg_quote($matches[1], '/') . '\t' . preg_quote($data[2], '/') . '\t/';
	} else {
		$neolines[] = $line;
		continue;
	}
	$findline = preg_grep($grep_pattern, $current);
	if ($findline) {
		if ($data[3]) {
			$newdata = explode("\t", rtrim(array_shift($findline)));
			$neolines[] = "\t{$newdata[1]}\t{$newdata[2]}\t{$data[3]}\n";
		} else {
			$neolines[] = $findline[0];
		}
	} else {
		$neolines[] = $line;
	}
}

//================================================
//書き込む
//================================================
if (serialize($lines) != serialize($neolines)) {
	$fp = @fopen($favita_path, 'wb') or die("Error: $favita_path を更新できませんでした");
	foreach ($neolines as $l) {
		fputs($fp, $l);
	}
	fclose($fp);
	$sync_ok = true;
} else {
	$sync_ok = false;
}

?>