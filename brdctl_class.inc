<?php
// p2 - BrdCtl -- ボードリストコントロールクラス for menu.php

require_once("./filectl_class.inc");
require_once("./datactl.inc");
require_once("./brdmenu_class.inc");


/**
* 板リスト コントロール クラス
*/
class BrdCtl{
	
	/**
	* boardを全て読み込む
	*/
	function read_brds()
	{
		$brd_menus_dir = BrdCtl::read_brd_dir();
		$brd_menus_online = BrdCtl::read_brd_online();
		$brd_menus = array_merge($brd_menus_dir, $brd_menus_online);
		return $brd_menus;
	}
	
	/**
	* boardディレクトリを走査して読み込む
	*/
	function read_brd_dir()
	{
		global $_info_msg_ht;
	
		$brd_menus = array();
		$brd_dir = "./board";
		
		if ($cdir = @dir($brd_dir)) {
			while( $entry = $cdir->read() ) {//ディレクトリ走査
				if($entry == "." || $entry == ".." || $entry == ".DS_Store"){ continue; }
				$filepath = $brd_dir."/".$entry;
				if( $data = @file($filepath) ){
					$aBrdMenu = new BrdMenu; //クラス BrdMenu のオブジェクトを生成
					$aBrdMenu->setBrdMatch($filepath); //パターンマッチ形式を登録
					$aBrdMenu->setBrdList($data); //カテゴリーと板をセット
					$brd_menus[] = $aBrdMenu;
					unset($data, $aBrdMenu);
				}else{
					$_info_msg_ht .= "<p>p2 エラー: 板リスト {$entry} が読み込めませんでした。</p>\n";
				}
			}
			$cdir->close();
		}
		
		return $brd_menus;
	}
	
	/**
	* オンライン板リストを読込む
	*/
	function read_brd_online()
	{
		global $_info_msg_ht, $brdfile_online, $c_menu_dl_interval;
		
		$brd_menus = array();
		
		if ($brdfile_online) {
			$cachefile = cacheForDL($brdfile_online);
			
			$noDL = false;
			if(  file_exists($cachefile.".p2.brd") ){	//キャッシュがある場合
				if($_GET['nr']){	// norefreshならDLしない
					$noDL = true;
				}elseif( @filemtime($cachefile.".p2.brd") > time() - 60*60*$c_menu_dl_interval){	//キャッシュの更新が指定時間以内ならDLしない
					$noDL = true;
				}
			}
			
			if( $noDL  ){	// DLしない
				;
			}else{	// DLする
				//echo "DL!<br>";//
				$brdfile_online_res = fileDownload($brdfile_online, $cachefile);
				if( $brdfile_online_res->is_success() && $brdfile_online_res->code!="304" ){
					$isNewDL = true;
				}
			}
		
			if( preg_match("/html?$/", $brdfile_online) ){ //html形式なら
			
				//更新されていたら新規キャッシュ作成
				if( $isNewDL ){
					//echo "NEW!<br>";//
					$aBrdMenu = new BrdMenu; //クラス BrdMenu のオブジェクトを生成
					$aBrdMenu->makeBrdFile($cachefile); //.p2.brdファイルを生成
					$brd_menus[] = $aBrdMenu;
					$read_html_flag = true;
					unset($aBrdMenu);
				}
				
				if( file_exists($cachefile.".p2.brd") ){
					$cashe_brd = $cachefile.".p2.brd";
				}else{
					$cashe_brd = $cachefile;
				}
				
			}else{
				$cashe_brd = $cachefile;
			}
			
			if(! $read_html_flag){
				if( $data = @file($cashe_brd) ){
					$aBrdMenu = new BrdMenu; //クラス BrdMenu のオブジェクトを生成
					$aBrdMenu->setBrdMatch($cashe_brd); //パターンマッチ形式を登録
					$aBrdMenu->setBrdList($data); //カテゴリーと板をセット
					if($aBrdMenu->num){
						$brd_menus[] = $aBrdMenu;
					}else{
						$_info_msg_ht .=  "<p>p2 エラー: {$cashe_brd} から板メニューを生成することはできませんでした。</p>\n";
					}
					unset($data, $aBrdMenu);
				}else{
					$_info_msg_ht .=  "<p>p2 エラー: {$cachefile} は読み込めませんでした。</p>\n";
				}
			}
		}
		
		return $brd_menus;
	}

}
?>