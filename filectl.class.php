<?php
/**
 * ファイルを操作するクラス
 * インスタンスを作らずにクラスメソッドで利用する
 */
class FileCtl{
	
	/**
	 * 書き込み用のファイルがなければ生成してパーミッションを調整する
	 */
	function make_datafile($file, $perm = 0606)
	{
		// 念のためにデフォルト補正しておく
		if (empty($perm)) {
			$perm = 0606;
		}
		
		if (!file_exists($file)) {
			// 親ディレクトリが無ければ作る
			FileCtl::mkdir_for($file) or die("Error: cannot make parent dirs. ( $file )");
			touch($file) or die("Error: cannot touch. ( $file )");
			chmod($file, $perm);
		} else {
			if (!is_writable($file)) {
				$cont = @file_get_contents($file);
				unlink($file);
				touch($file);
				
				// 書き込む
				$fp = @fopen($file, "wb") or die("Error: cannot write. ( $file )");
				@flock($fp, LOCK_EX);
				fputs($fp, $cont);
				@flock($fp, LOCK_UN);
				fclose($fp);
				chmod($file, $perm);
			}		
		}
		return true;
	}
	
	/**
	 * 親ディレクトリがなければ生成してパーミッションを調整する
	 */
	function mkdir_for($apath)
	{
		global $_conf;
		
		$dir_limit = 50;	// 親階層を上る制限回数
		
		$perm = (!empty($_conf['data_dir_perm'])) ? $_conf['data_dir_perm'] : 0707;

		if (!$parentdir = dirname($apath)) {
			die("Error: cannot mkdir. ( {$parentdir} )<br>親ディレクトリが空白です。");
		}
		$i = 1;
		if (!is_dir($parentdir)) {
			if ($i > $dir_limit) {
				die("Error: cannot mkdir. ( {$parentdir} )<br>階層を上がり過ぎたので、ストップしました。");
			}
			FileCtl::mkdir_for($parentdir);
			mkdir($parentdir, $perm) or die("Error: cannot mkdir. ( {$parentdir} )");
			chmod($parentdir, $perm);
			$i++;
		}
		return true;
	}
	
	/**
	 * gzファイルの中身を取得する
	 */
	function get_gzfile_contents($filepath)
	{
		if (is_readable($filepath)) {
			ob_start();
	    	readgzfile($filepath);
	    	$contents = ob_get_contents();
	   		ob_end_clean();
	    	return $contents;
		} else {
			return false;
		}
	}
	
	/**
	 * 文字列をファイルに書き込む
	 * （PHP5のfile_put_contentsの代替）
	 */
	function file_write_contents($file, $cont)
	{
		if (!$fp = @fopen($file, 'wb')) {
			return false;
		}
		@flock($fp, LOCK_EX);
		fwrite($fp, $cont);
		@flock($fp, LOCK_UN);
		fclose($fp);
		
		return true;
	}
	
}
?>
