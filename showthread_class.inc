<?php
// p2 - スレッドを表示する クラス

class ShowThread{
	var $thread; //スレッドオブジェクト
	
	function ShowThread($aThread){
		$this->thread = $aThread;
	}

	//===================================
	// 透明あぼーんメソッド
	//===================================
	function aborn($aborn_list, $resfield){
		if($aborn_list){
			foreach($aborn_list as $an_aborn_word){
				$an_aborn_word = trim($an_aborn_word);
				if( @strstr($resfield, $an_aborn_word) ){
					return true;
				}
			}
		}
		return false;
	}

	//===================================
	// NGチェックメソッド
	//===================================
	function ngCheck($ng_list, $resfield){
		if($ng_list){
			foreach($ng_list as $a_ng_word){
				$a_ng_word = trim($a_ng_word);
				if( @strstr($resfield, $a_ng_word) ){
					return $a_ng_word;
				}
			}
		}
		return false;
	}

}
?>