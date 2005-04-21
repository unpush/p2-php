<?php
// p2 - 書き込み履歴 のための関数群

require_once './dataphp.class.php';

//======================================================================
// 関数
//======================================================================
/**
 * チェックした書き込み記事を削除する
 */
function deleMsg($checked_hists)
{
	global $p2_res_hist_dat_php;

	// 読み込んで
	if (!$reslines = DataPhp::fileDataPhp($p2_res_hist_dat_php)) {
		die("p2 Error: {$p2_res_hist_dat_php} を開けませんでした");
	}
	$reslines = array_map('rtrim', $reslines);
	
	// ファイルの下に記録されているものが新しいので逆順にする
	$reslines = array_reverse($reslines);
	
	// チェックして整えて
	if ($reslines) {
		$n = 1;
		foreach ($reslines as $ares) {
			$rar = explode("\t", $ares);
			
			// 番号と日付が一致するかをチェックする
			if (checkMsgID($checked_hists, $n, $rar[2])) {
				$rmnums[] = $n; // 削除する番号を登録
			}
			
			$n++;
		}
		$neolines = rmLine($rmnums, $reslines);
	}
	
	if (is_array($neolines)) {
		// 行順を戻す
		$neolines = array_reverse($neolines);
		
		$cont = "";
		if ($neolines) {
			$cont = implode("\n", $neolines) . "\n";
		}
		// 書き込み処理
		DataPhp::writeDataPhp($cont, $p2_res_hist_dat_php);
	}
}

/**
 * 番号と日付が一致するかをチェックする
 */
function checkMsgID($checked_hists, $order, $date)
{
	if ($checked_hists) {
		foreach ($checked_hists as $v) {
			$vary = explode(",,,,", $v);	// ",,,," は外部から来る変数で、特殊なデリミタ
			if (($vary[0] == $order) and ($vary[1] == $date)) {
				return true;
			}
		}
	}
	return false;
}

/**
 * 指定した番号（配列指定）を行リストから削除する
 */
function rmLine($order_list, $lines)
{
	if ($lines) {
		$neolines = array();
		$i = 0;
		foreach ($lines as $l) {
			$i++;
			if (checkOrder($order_list, $i)) { continue; } // 削除扱い
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
			if ($n == $order) {
				return true;
			}
		}
	}
	return false;
}

?>