<?php
// p2 - 書き込み履歴 のための関数群

//======================================================================
// 関数
//======================================================================
/**
 * チェックした書き込み記事を削除する関数
 */
function deleMsg($checked_hists)
{
	global $p2_res_hist_dat_php;

	// 読み込んで
	if (!$reslines = P2Util::fileDataPhp($p2_res_hist_dat_php)) {
		die("p2 Error: {$p2_res_hist_dat_php} を開けませんでした");
	}

	// チェックして整えて
	$neolines = "";
	if ($reslines) {
		$n = 1;
		foreach ($reslines as $ares) {
			// &<>/ → &xxx; のエスケープを元に戻す
			$ares = P2Util::unescapeDataPhp($ares);
			
			$rar = explode("\t", $ares);
			
			// 番号と日付が一致するかをチェックする
			if (checkMsgID($checked_hists, $n, $rar[2])) {
				$rmnums[] = $n; // 削除する番号を登録
			}
			
			$n++;
		}
		$neolines = rmLine($rmnums, $reslines);
	}
	
	// 先頭文を追加
	@array_unshift($neolines, '<?php /*');
	// 末文を追加
	@array_push($neolines, '*/ ?>');
	
	// 書き込む
	$fp = @fopen($p2_res_hist_dat_php, "wb") or die("p2 Error: {$p2_res_hist_dat_php} を更新できませんでした");
	if ($neolines) {
		foreach ($neolines as $l) {			
			// データ行を記録
			fputs($fp, $l."\n");
		}
	}
	fclose($fp);
}

/**
 * 番号と日付が一致するかをチェックする関数
 */
function checkMsgID($checked_hists, $order, $date)
{
	if ($checked_hists) {
		foreach ($checked_hists as $v) {
			$vary = explode(",,,,", $v);	// ",,,," は外部から来る変数で、特殊なデリミタ
			//echo "$vary[0]==$order,$vary[1]==$date<br>";
			if (($vary[0]==$order) and ($vary[1]==$date)) {
				return true;
			}
		}
	}
	return false;
}

/**
 * 指定した番号（配列指定）を行リストから削除する関数
 */
function rmLine($order_list, $lines)
{
	if ($lines) {
		$i = 0;
		foreach ($lines as $l) {
			$i++;
			if( checkOrder($order_list, $i) ){ continue; } // 削除扱い
			$neolines[] = $l;
		}
		return $neolines;
	}
	return false;
}

/**
 * 番号と配列を比較
 */
function checkOrder($order_list, $order)
{
	if ($order_list) {
		foreach ($order_list as $n) {
			if ($n==$order) {
				return true;
			}
		}
	}
	return false;
}

?>