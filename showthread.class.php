<?php
// p2 - スレッドを表示する クラス

class ShowThread{

	var $thread; // スレッドオブジェクト
	
	
	/**
	 * コンストラクタ
	 */
	function ShowThread($aThread)
	{
		$this->thread = $aThread;
	}

	/**
	 * BEプロファイルリンク変換
	 */
	function replaceBeId($date_id)
	{
		global $_conf;
		
		$beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/{$this->thread->key}/\"{$_conf['ext_win_target']}>Lv.\$2</a>";		
		
		//<BE:23457986:1>
		$be_match = '|<BE:(\d+):(\d+)>|i';
		if (preg_match($be_match, $date_id)) {
			$date_id = preg_replace($be_match, $beid_replace, $date_id);
		
		} else {
		
			$beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/{$this->thread->key}/\"{$_conf['ext_win_target']}>?\$2</a>";
			$date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
		}
		
		return $date_id;
	}

	/**
	 * NGあぼーんチェック
	 */
	function ngAbornCheck($code, $resfield)
	{
		global $ngaborns;
		
		if (is_array($ngaborns[$code]['data'])) {
			foreach ($ngaborns[$code]['data'] as $k => $v) {
				if (@strstr($resfield, $ngaborns[$code]['data'][$k]['word'])) {
					$ngaborns[$code]['data'][$k]['lasttime'] = date('Y/m/d G:i');	// HIT時間を更新
					$ngaborns[$code]['data'][$k]['hits']++;	// HIT回数を更新
					return $ngaborns[$code]['data'][$k]['word'];
				}
			}
		}
		return false;
	}

	/**
	 * 特定レスの透明あぼーんチェック
	 */
	function abornResCheck($host, $bbs, $key, $resnum)
	{
		global $ngaborns;
		
		$target = $host . '/' . $bbs . '/' . $key . '/' . $resnum;
		
		if ($ngaborns['aborn_res']['data']) {
			foreach ($ngaborns['aborn_res']['data'] as $k => $v) {
				if ($ngaborns['aborn_res']['data'][$k]['word'] == $target) {
					$ngaborns['aborn_res']['data'][$k]['lasttime'] = date('Y/m/d G:i');	// HIT時間を更新
					$ngaborns['aborn_res']['data'][$k]['hits']++;	// HIT回数を更新
					return true;
				}
			}
		}
		return false;
	}

}
?>