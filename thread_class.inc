<?php
// p2 - スレッド クラス

require_once './p2util.class.php';	// p2用のユーティリティクラス

//=============================================================================
// スレッドクラス
//=============================================================================

class Thread{
	var $ttitle; //スレタイトル //datline[0]
	var $key; //スレッドID //datline[1]
	var $length; //local Dat Bytes(int) //datline[2]
	var $rnum; //既読レス数 //datline[3]
	var $rescount; //総レス数
	var $modified; //datのLast-Modified //datline[4]
	//last res
	var $fav; //お気に入り(bool) //datline[6]
	//name
	//mail
	var $newline; //新規レス番 //datline[9]
	
	var $host; // ex)pc.2ch.net //datline[10]
	var $bbs; // ex)mac //datline[11]
	var $itaj; //板名 ex)新・mac
	
	var $torder; //スレッド新しい順番号
	var $unum; //未読（新着レス）数
	var $kitoku; //既得かどうか(bool)
	
	var $keyidx; //idxファイルパス
	var $keydat; //ローカルdatファイルパス
	
	var $isonline; //板サーバにあればtrue
	var $new; //新規スレならtrue
	var $ttitle_ht; //HTML表示用タイトル
	var $dayres; //一日当たりのレス数。勢い。
	
	var $dat_type; // datの形式（2chの旧形式dat（,区切り）なら"2ch_old"）

	function Thread(){
	}

	//============================================================================
	// getThreadInfoFromExtIdxLine -- fav, recent用の拡張idxリストからラインデータを取得するメソッド
	//============================================================================	

	function getThreadInfoFromExtIdxLine($l){
		$l = rtrim($l);
		$la = explode("<>", $l);
		$this->host = $la[10];
		$this->bbs = $la[11];
		$this->key = $la[1];
		
		if(! $this->ttitle){
			if($la[0]){
				$this->ttitle=rtrim($la[0]);
			}
		}
		
		//if($la[6]){$this->fav = $la[6];}
	}

	//============================================================================
	// setThreadPathInfo -- Set Path infoメソッド
	//============================================================================	

	function setThreadPathInfo($host, $bbs, $key){	
		$this->host = $host;
		$this->bbs = $bbs;
		$this->key = $key;
		
		$datdir_host = datdirOfHost($this->host);
		$this->keyidx = "{$datdir_host}/{$this->bbs}/{$this->key}.idx";
		$this->keydat = "{$datdir_host}/{$this->bbs}/{$this->key}.dat";
	}

	//============================================================================
	// getThreadInfoFromIdx -- 既得スレッドデータをkey.idxから取得するメソッド
	//============================================================================	

	function getThreadInfoFromIdx($keyidx){
		if(! $lines=@file($keyidx) ){
			return false;
		}
		
		$key_line = rtrim($lines[0]);
		$lar = explode("<>", $key_line);
		if(! $this->ttitle){
			if($lar[0]){
				$this->ttitle = rtrim($lar[0]);
			}
		}
		
		if($lar[9]){
			$this->newline = $lar[9];
		}else{
			$this->newline = 1;
		}
		
		if($lar[3]){
			$this->kitoku = true;
			$this->rnum = $lar[3];
			if($this->rescount){
				$this->unum = $this->rescount - ($this->newline -1);
				if($this->unum<0){ $this->unum=0; } //machi bbs はsubjectの更新にディレイがあるようなので調整しておく
			}
		}
		//if($lar[2]){ $this->length=$lar[2]; }
		if($lar[4]){ $this->modified = $lar[4]; }
		
		return $key_line; 
	}
	
	//============================================================================
	// getDatBytesFromLocalDat -- ローカルDATのファイルサイズを取得するメソッド
	//============================================================================	

	function getDatBytesFromLocalDat(){
		clearstatcache();
		if( $this->length=@filesize($this->keydat) ){
			return $this->length;
		}else{
			return false;
		}
	}
	
	//============================================================================
	// subject.txt の一行からスレ情報を取得するメソッド
	//============================================================================	
	function getThreadInfoFromSubjectTxtLine($l)
	{
		preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches);
		$this->isonline = true;
		$this->key = $matches[1];
		$this->ttitle = rtrim($matches[4]);
		
		// be.2ch.net ならEUC→SJIS変換
		if (P2Util::isHostBe2chNet($this->host)) {
			include_once './strctl_class.inc';
			$this->ttitle = StrCtl::p2EUCtoSJIS($this->ttitle);
		}
		
		$this->rescount = $matches[6];
		if($this->newline){
			$this->unum = $this->rescount - ($this->newline -1);
			if($this->unum<0){$this->unum=0;} //machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
		}
	}

	//=========================================
	// スレタイトル取得メソッド
	//=========================================
	function setTitleFromLocal()
	{
		if (!$this->ttitle) {
		
			if ($this->datlines) {
				$firstdatline = rtrim($this->datlines[0]);
				$d = $this->explodeDatLine($firstdatline);
				$this->ttitle = $d[4];
			
			// ローカルdatの1行目から取得
			} elseif (is_readable($this->keydat)){
				$fd = fopen($this->keydat, "r");
				$l = fgets ($fd,32800);
				fclose ($fd);
				$firstdatline = rtrim($l);
				if (strstr($firstdatline, "<>")) {
					$datline_sepa = "<>";
				} else {
					$datline_sepa = ",";
					$this->dat_type = "2ch_old";
				}
				$d = explode($datline_sepa, $firstdatline);
				$this->ttitle = $d[4];
				
				// be.2ch.net ならEUC→SJIS変換
				if (P2Util::isHostBe2chNet($this->host)) {
					include_once './strctl_class.inc';
					$this->ttitle = StrCtl::p2EUCtoSJIS($this->ttitle);
				}
			}
			
		}
		
		return $this->ttitle;
	}

	/**
	 * ■元スレURLを返すメソッド
	 */
	function getMotoThread($ls = "")
	{
		global $ktai;
		
		if (P2Util::isHostMachiBbs($this->host)) {
			$motothre_url = "http://{$this->host}/bbs/read.pl?BBS={$this->bbs}&KEY={$this->key}";
		} elseif (P2Util::isHostMachiBbsNet($this->host)) {
			$motothre_url = "http://{$this->host}/test/read.cgi?bbs={$this->bbs}&key={$this->key}";	
		} elseif (P2Util::isHostJbbsShitaraba($this->host)) {
			$motothre_url = "http://{$this->host}/bbs/read.cgi?BBS={$this->bbs}&KEY={$this->key}";
		} elseif (P2Util::isHost2chs($this->host)) {
			if ($ktai) {
				if (P2Util::isHostBbsPink($this->host)) {
					$motothre_url = "http://{$this->host}/test/r.i/{$this->bbs}/{$this->key}/{$ls}";
				} else {
					$motothre_url = "http://c.2ch.net/test/-/{$this->bbs}/{$this->key}/{$ls}";
				}
			} else {
				$motothre_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/{$ls}";
			}
		} else {
			$motothre_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/{$ls}";
		}
		
		return $motothre_url;
	}
	
	/**
	 * ■勢い（レス/日）をセットする
	 */
	function setDayRes($nowtime = false)
	{
		if (!isset($this->key) || !isset($this->rescount)) {
			return false;
		}
		
		if (!$nowtime) {
			$nowtime = time();
		}
		$pastsc = $nowtime - $this->key;
		$this->dayres = $this->rescount / $pastsc * 60 * 60 * 24;
		return true;
	}

	/**
	 * ■レス間隔（時間/レス）を取得する
	 */
	function getTimePerRes()
	{
		$noresult_st = "-";
	
		if (!isset($this->dayres)) {
			if (!$this->setDayRes(time())) {
				return $noresult_st;
			}
		}
		
		if ($this->dayres <= 0) {
			return $noresult_st;
			
		} elseif ($this->dayres < 1/365) {
			$spd = 1/365 / $this->dayres;
			$spd_suffix = "年";
		} elseif ($this->dayres < 1/30.5) {
			$spd = 1/30.5 / $this->dayres;
			$spd_suffix = "ヶ月";
		} elseif ($this->dayres < 1) {
			$spd = 1 / $this->dayres;
			$spd_suffix = "日";
		} elseif ($this->dayres < 24) {
			$spd = 24 / $this->dayres;
			$spd_suffix = "時間";
		} elseif ($this->dayres < 24*60) {
			$spd = 24*60 / $this->dayres;
			$spd_suffix = "分";
		} elseif ($this->dayres < 24*60*60) {
			$spd = 24*60*60 / $this->dayres;
			$spd_suffix = "秒";
		} else {
			$spd = 1;
			$spd_suffix = "秒以下";
		}
		if ($spd > 0) {
			$spd_st = sprintf("%01.1f", @round($spd, 2)) . $spd_suffix;
		} else {
			$spd_st = "-";
		}
		return $spd_st;
	}

}
?>