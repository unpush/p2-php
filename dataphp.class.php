<?php

/*
	データファイルにWebから直接アクセスされても中をみられないようにphp形式のファイルでデータを取り扱うクラス
	インスタンスを作らずにクラスメソッドで利用する。ファイルの保存形式は、以下のような感じ。
	
	＜？php ／*
	データ
	*／ ？＞
*/

class DataPhp{

	function getPre()
	{
		return "<?php /*\n";
	}

	function getHip()
	{
		return "\n*/ ?>";
	}

	/**
	 * ■データphp形式のファイルを読み込む
	 *
	 * 文字列のアンエスケープも行う
	 */
	function getDataPhpCont($data_php)
	{
		if (!$cont = @file_get_contents($data_php)) {
			// 読み込みエラーならfalse、空っぽなら""を返す
			return $cont;
			
		} else {
			$pre_quote = preg_quote(DataPhp::getPre());
			$hip_quote = preg_quote(DataPhp::getHip());
			// 先頭文と末文を削除
			$cont = preg_replace("{".$pre_quote."(.*)".$hip_quote.".*}s", "$1", $cont);
			// アンエスケープする
			$cont = DataPhp::unescapeDataPhp($cont);

			return $cont;
		}
	}
	
	/**
	 * ■データphp形式のファイルをラインで読み込む
	 *
	 * 文字列のアンエスケープも行う
	 */
	function fileDataPhp($data_php)
	{
		if (!$cont = DataPhp::getDataPhpCont($data_php)) {
			// 読み込みエラーならfalse、空っぽなら空配列を返す
			if ($cont === false) {
				return false;
			} else {
				return array();
			}
		} else {

			// 行データに変換
			$lines = array();
			while (strlen($cont) > 0) {
				if (preg_match("{(.*?\n)(.*)}s", $cont, $matches)) {
					$lines[] = $matches[1];
					$cont = $matches[2];
				} else {
					$lines[] = $cont;
					break;
				}
			}
			
			/*
			if ($lines) {
				// 末尾の空行は特別に削除する
				$count = count($lines);
				if (rtrim($lines[$count-1]) == "") {
					array_pop($lines);
				}
			}
			*/
			
			//var_dump($lines);
			return $lines;
		}
	}

	/**
	 * データphp形式のファイルにデータを記録する
	 *
	 * 文字列のエスケープも行う
	 * @param srting $cont 記録するデータ文字列。
	 */
	function writeDataPhp($cont, $data_php, $perm = 0606)
	{
		// &<>/ を &xxx; にエスケープして
		$new_cont = DataPhp::escapeDataPhp($cont);
		
		// 先頭文と末文を追加
		$new_cont = DataPhp::getPre().$new_cont.DataPhp::getHip();
		
		// ファイルがなければ生成
		FileCtl::make_datafile($data_php, $perm);
		// 書き込む
		$fp = @fopen($data_php, 'wb') or die("Error: {$data_php} を更新できませんでした");
		@flock($fp, LOCK_EX);
		fwrite($fp, $new_cont);
		@flock($fp, LOCK_UN);
		fclose($fp);
		
		return true;
	}
	
	/**
	 * データphp形式のファイルで、末尾にデータを追加する
	 */
	function putDataPhp($cont, $data_php, $perm = 0606)
	{
		$pre_quote = preg_quote(DataPhp::getPre());
		$hip_quote = preg_quote(DataPhp::getHip());

		$cont_esc = DataPhp::escapeDataPhp($cont);

		$old_cont = @file_get_contents($data_php);
		if ($old_cont) {
			$new_cont = preg_replace('{('.$hip_quote.'.*$)}s', '', $old_cont) . $cont_esc .DataPhp::getHip();
		} else {
			$new_cont = DataPhp::getPre().$cont.DataPhp::getHip();
		}
		
		// ファイルがなければ生成
		FileCtl::make_datafile($data_php, $perm);
		// 書き込む
		$fp = @fopen($data_php, 'wb') or die("Error: {$data_php} を更新できませんでした");
		@flock($fp, LOCK_EX);
		fwrite($fp, $new_cont);
		@flock($fp, LOCK_UN);
		fclose($fp);
	}
	
	/**
	 * ■データphp形式のデータをエスケープする
	 */
	function escapeDataPhp($str)
	{
		// &<>/ → &xxx; のエスケープをする
		$str = str_replace("&", "&amp;", $str);
		$str = str_replace("<", "&lt;", $str);
		$str = str_replace(">", "&gt;", $str);
		$str = str_replace("/", "&frasl;", $str);
		return $str;
	}

	/**
	 * ■データphp形式のデータをアンエスケープする
	 */
	function unescapeDataPhp($str)
	{
		// &<>/ → &xxx; のエスケープを元に戻す
		$str = str_replace('&lt;', '<', $str);
		$str = str_replace('&gt;', '>', $str);
		$str = str_replace('&frasl;', '/', $str);
		$str = str_replace('&amp;', '&', $str);	
		return $str;
	}

}

?>