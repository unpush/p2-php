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