<?php
// p2 - スレッドあぼーん複数一括解除処理

require_once("datactl.inc");

//====================================================================
// スレッドあぼーんを複数一括解除する関数
//====================================================================
function settaborn_off($host, $bbs, $taborn_off_keys){
	
	//==============================================================
	// p2_threads_aborn.idxに書き込む
	//==============================================================
	
	// p2_threads_aborn.idx のパス取得
	$datdir_host = datdirOfHost($host);
	$taborn_idx = "{$datdir_host}/{$bbs}/p2_threads_aborn.idx";
	
	// p2_threads_aborn.idx がなければ
	if(! file_exists($taborn_idx) ){die("あぼーんリストが見つかりませんでした。");}
	
	// p2_threads_aborn.idx 読み込み
	$taborn_lines= @file($taborn_idx);
	
	// 指定keyを削除 ===================================
	if ($taborn_off_keys) {
		
		foreach ($taborn_off_keys as $val) {
			$neolines = "";
			
			if ($taborn_lines) {
				foreach ($taborn_lines as $line) {
					$line = rtrim($line);
					$lar = explode('<>', $line);
					if ($lar[1] == $val) { // key発見
						// echo "key:{$val} のスレッドをあぼーん解除しました。<br>";
						$kaijo_attayo = true;
						continue;
					}
					if (!$lar[1]) { continue; } // keyのないものは不正データ
					$neolines[] = $line;
				}
			}
			
			$taborn_lines = $neolines;
		}
		
		// 書き込む ====================================================
		if (file_exists($taborn_idx)) {
			copy($taborn_idx, $taborn_idx.'.bak'); // 念のためバックアップ
		}
		$fp = @fopen($taborn_idx, "wb") or die("Error: $taborn_idx を更新できませんでした");
		if ($neolines) {
			foreach ($neolines as $l) {
				fputs($fp, $l."\n");
			}
		}
		fclose($fp);
	
		/*
		if(! $kaijo_attayo){
			//echo "指定されたスレッドは既にあぼーんリストに載っていないようです。";
		}else{
			//echo "あぼーん解除、完了しました。";
		}
		*/		
	
	}

}

?>