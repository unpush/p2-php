<?php
/*
	p2 - ボードメニュークラス for menu.php
*/

require_once './filectl.class.php';

/**
 * ボードメニュークラス
 */
class BrdMenu{

	var $categories; // クラス BrdMenuCate のオブジェクトを格納する配列
	var $num; // 格納された BrdMenuCate オブジェクトの数
	var $format; // html形式か、brd形式か("html", "brd")
	var $cate_match; // カテゴリーマッチ
	var $ita_match; // 板マッチ
	
	function BrdMenu(){
		$this->num=0;
	}
	
	//カテゴリーを追加するメソッド=================================
	function addBrdMenuCate($aBrdMenuCate)
	{
		$this->categories[] = $aBrdMenuCate;
		$this->num++;
	}
	
	//パターンマッチの形式を登録するメソッド==========================
	function setBrdMatch($brdName)
	{
		if( preg_match("/html?$/", $brdName) ){ //html形式
			$this->format = "html";
			$this->cate_match="/<B>(.+)<\/B><BR>.*$/i";
			$this->ita_match="/^<A HREF=\"?(http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?)\"?( target=\"?_blank\"?)?>(.+)<\/A>(<br>)?$/i";
		}else{// brd形式
			$this->format = "brd";
			$this->cate_match="/^(.+)	([0-9])$/";
			$this->ita_match="/^\t?(.+)\t(.+)\t(.+)$/";
		}
	}

	//データを読み込んで、カテゴリと板を登録するメソッド===================
	function setBrdList($data)
	{
		global $_conf, $word, $word_fm, $mikke;
		
		if(!$data){return false;}

		//除外URLリスト
		$not_bbs_list = array("http://members.tripod.co.jp/Backy/del_2ch/");
	
		foreach($data as $v){
			$v = rtrim($v);
			
			//カテゴリを探す
			if( preg_match($this->cate_match, $v, $matches) ){
				$aBrdMenuCate = new BrdMenuCate;
				$aBrdMenuCate->name = $matches[1];
				if($this->format == "brd"){ $aBrdMenuCate->is_open = $matches[2]; }
				$this->addBrdMenuCate($aBrdMenuCate);
			//板を探す
			}elseif(preg_match($this->ita_match, $v, $matches)){
				if($this->format == "html"){// html形式なら除外URLを外す
					foreach($not_bbs_list as $not_a_bbs){
						if($not_a_bbs==$matches[1]){ continue 2; }
					}
				}
				$aBrdMenuIta = new BrdMenuIta;
				if($this->format == "html"){  // html形式
					$aBrdMenuIta->host = $matches[2];
					$aBrdMenuIta->bbs = $matches[3];
					$itaj_match = $matches[6];
				}else{ //brd形式
					$aBrdMenuIta->host = $matches[1];
					$aBrdMenuIta->bbs = $matches[2];
					$itaj_match = $matches[3];
				}
				$aBrdMenuIta->itaj = rtrim($itaj_match);
				$aBrdMenuIta->itaj_en = base64_encode($aBrdMenuIta->itaj);
				
				// 板検索マッチ ===================================
				$aBrdMenuIta->itaj_ht = $aBrdMenuIta->itaj;

				// 正規表現検索
				if ($word_fm) {
					if (StrCtl::filterMatch($word_fm, $aBrdMenuIta->itaj)) {
						$this->categories[$this->num-1]->match_attayo = true;
						$GLOBALS['ita_mikke']['num']++;

						// マーキング
						$aBrdMenuIta->itaj_ht = StrCtl::filterMarking($word_fm, $aBrdMenuIta->itaj);
						
					} else { // 検索が見つからなくて、さらに携帯の時
						if ($_conf['ktai']) {
							continue;
						}
					}
				}

				if($this->num){
					$this->categories[$this->num-1]->addBrdMenuIta($aBrdMenuIta);
				}
			}
		}
	}

	/**
	* brdファイルを生成するメソッド
	*
	* @return	string	brdファイルのパス
	*/
	function makeBrdFile($cachefile)
	{
	global $_conf, $_info_msg_ht, $word;
	
		$p2brdfile = $cachefile.".p2.brd";
		FileCtl::make_datafile($p2brdfile, $_conf['p2_perm']);
		$data = @file($cachefile);
		$this->setBrdMatch($cachefile); //パターンマッチ形式を登録
		$this->setBrdList($data); //カテゴリーと板をセット
		if($this->categories){
			foreach($this->categories as $cate){
				if($cate->num > 0){
					$cont .= $cate->name."\t0\n";
					foreach($cate->menuitas as $mita){
						$cont .= "\t{$mita->host}\t{$mita->bbs}\t{$mita->itaj}\n";
					}
				}
			}
		}

		if($cont){
			$fp = @fopen($p2brdfile, 'wb') or die("p2 error: {$p2brdfile} を更新できませんでした");
			@flock($fp, LOCK_EX);
			fputs($fp, $cont);
			@flock($fp, LOCK_UN);
			fclose($fp);
			return $p2brdfile;
		}else{
			if(!$word){
				$_info_msg_ht .=  "<p>p2 エラー: {$cachefile} から板メニューを生成することはできませんでした。</p>\n";
			}
			return false;
		}
	}
	
}

//==========================================================
// ボードメニューカテゴリークラス
//==========================================================
class BrdMenuCate{
	var $name; //カテゴリーの名前
	var $menuitas; //クラスBrdMenuItaのオブジェクトを格納する配列
	var $num; //格納されたBrdMenuItaオブジェクトの数
	var $is_open; //開閉状態(bool)
	var $match_attayo;
	
	function BrdMenuCate(){
		$this->num=0;
	}
	
	function addBrdMenuIta($aBrdMenuIta){
		$this->menuitas[] = $aBrdMenuIta;
		$this->num++;
	}
	
}

//==========================================================
// ボードメニュー板クラス
//==========================================================
class BrdMenuIta{
	var $host;
	var $bbs;
	var $itaj;
	var $itaj_en;
	var $itaj_ht;
}

?>