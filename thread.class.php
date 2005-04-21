<?php
// p2 - スレッド クラス

require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './filectl.class.php';

/**
 * ■スレッドクラス
 */
class Thread{

	var $ttitle; // スレタイトル // idxline[0] // < は &lt; だったりする
	var $key; // スレッドID // idxline[1]
	var $length; // local Dat Bytes(int) // idxline[2]
	var $gotnum; //（個人にとっての）既得レス数 // idxline[3]
	var $rescount; // スレッドの総レス数（未取得分も含む）
	var $modified; // datのLast-Modified // idxline[4]
	var $readnum; // 既読レス数	// idxline[5] // MacMoeではレス表示位置だったと思う（last res）
	var $fav; //お気に入り(bool的に) // idxline[6] favlist.idxも参照
	// name // ここでは利用せず idxline[7]（他所で利用）
	// mail // ここでは利用せず idxline[8]（他所で利用）
	// var $newline; // 次の新規取得レス番号 // idxline[9] 廃止予定。旧互換のため残してはいる。
	
	// ※hostとはいうものの、2ch外の場合は、host以下のディレクトリまで含まれていたりする。
	var $host; // ex)pc.2ch.net // idxline[10]
	var $bbs; // ex)mac // idxline[11]
	var $itaj; // 板名 ex)新・mac
	
	var $torder; // スレッド新しい順番号
	var $unum; // 未読（新着レス）数
	
	var $keyidx;	// idxファイルパス
	var $keydat;	// ローカルdatファイルパス
	
	var $isonline; // 板サーバにあればtrue。subject.txtやdat取得時に確認してセットされる。
	var $new; // 新規スレならtrue
	
	var $ttitle_hc;	// < が &lt; であったりするので、デコードしたスレタイトル
	var $ttitle_hd;	// HTML表示用に、エンコードされたスレタイトル
	var $ttitle_ht;	// スレタイトル表示用HTMLコード。フィルタリング強調されていたりも。
	
	var $dayres; // 一日当たりのレス数。勢い。
	
	var $dat_type; // datの形式（2chの旧形式dat（,区切り）なら"2ch_old"）
	
	/**
	 * コンストラクタ
	 */
	function Thread()
	{
	}

	/**
	 * ttitleをセットする（ついでにttitle_hc, ttitle_hd, ttitle_htも）
	 */
	function setTtitle($ttitle)
	{
		$this->ttitle = $ttitle;
		// < が &lt; であったりするので、まずデコードしたものを
		$this->ttitle_hc = html_entity_decode($this->ttitle, ENT_COMPAT, 'Shift_JIS');	
		// HTML表示用に htmlspecialchars() したもの
		$this->ttitle_hd = htmlspecialchars($this->ttitle_hc);
		$this->ttitle_ht = $this->ttitle_hd;
	}
	
	/**
	 * fav, recent用の拡張idxリストからラインデータを取得する
	 */

	function getThreadInfoFromExtIdxLine($l)
	{
		$la = explode('<>', rtrim($l));
		$this->host = $la[10];
		$this->bbs = $la[11];
		$this->key = $la[1];
		
		if (!$this->ttitle) {
			if ($la[0]) {
				$this->setTtitle(rtrim($la[0]));
			}
		}
		
		/*
		if ($la[6]) {
			$this->fav = $la[6];
		}
		*/
	}

	/**
	 * ■ Set Path info
	 */
	function setThreadPathInfo($host, $bbs, $key)
	{	
		$this->host = $host;
		$this->bbs = $bbs;
		$this->key = $key;
		
		$datdir_host = P2Util::datdirOfHost($this->host);
		$this->keyidx = "{$datdir_host}/{$this->bbs}/{$this->key}.idx";
		$this->keydat = "{$datdir_host}/{$this->bbs}/{$this->key}.dat";
	}

	/**
	 * ■スレッドが既得済みならtrueを返す
	 */
	function isKitoku()
	{
		// if (file_exists($this->keyidx)) {
		if ($this->gotnum || $this->readnum || $this->newline > 1) {
			return true;
		}
		return false;
	}

	/**
	 * ■既得スレッドデータをkey.idxから取得する
	 */
	function getThreadInfoFromIdx()
	{
		if (!$lines = @file($this->keyidx)) {
			return false;
		}
		
		$key_line = rtrim($lines[0]);
		$lar = explode('<>', $key_line);
		if (!$this->ttitle) {
			if ($lar[0]) {
				$this->setTtitle(rtrim($lar[0]));
			}
		}
		
		if ($lar[5]) {
			$this->readnum = $lar[5];
		
		// 旧互換措置（$lar[9] newlineの廃止）
		} elseif ($lar[9]) {
			$this->readnum = $lar[9] -1;
		}
		
		if ($lar[3]) {
			$this->gotnum = $lar[3];
		
			if ($this->rescount) {
				$this->unum = $this->rescount - $this->readnum;
				// machi bbs はsubjectの更新にディレイがあるようなので調整しておく
				if ($this->unum < 0) {
					$this->unum = 0;
				}
			}
		}

		if ($lar[6]) {
			$this->fav = $lar[6];
		}
		
		/*
		// 現在key.idxのこのカラムは使用していない。datサイズは直接ファイルの大きさを読み取って調べる
		if ($lar[2]) {
			$this->length = $lar[2];
		}
		*/
		if ($lar[4]) { $this->modified = $lar[4]; }
		
		return $key_line; 
	}
	
	/**
	 * ■ローカルDATのファイルサイズを取得する
	 */
	function getDatBytesFromLocalDat()
	{
		clearstatcache();
		if ($this->length = @filesize($this->keydat)) {
			return $this->length;
		} else {
			return false;
		}
	}
	
	/**
	 * ■ subject.txt の一行からスレ情報を取得する
	 */
	function getThreadInfoFromSubjectTxtLine($l)
	{
		if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches)) {
			$this->isonline = true;
			$this->key = $matches[1];
			$this->setTtitle(rtrim($matches[4]));
		
			// be.2ch.net ならEUC→SJIS変換
			if (P2Util::isHostBe2chNet($this->host)) {
				$ttitle = mb_convert_encoding($this->ttitle, 'SJIS-win', 'EUC-JP');
				$this->setTtitle($ttitle);
			}
		
			$this->rescount = $matches[6];
			if ($this->readnum) {
				$this->unum = $this->rescount - $this->readnum;
				// machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
				if ($this->unum < 0) {
					$this->unum = 0;
				}
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * ■スレタイトル取得メソッド
	 */
	function setTitleFromLocal()
	{
		if (!isset($this->ttitle)) {
		
			if ($this->datlines) {
				$firstdatline = rtrim($this->datlines[0]);
				$d = $this->explodeDatLine($firstdatline);
				$this->setTtitle($d[4]);
			
			// ローカルdatの1行目から取得
			} elseif (is_readable($this->keydat)) {
				$fd = fopen($this->keydat, "rb");
				$l = fgets($fd, 32800);
				fclose($fd);
				$firstdatline = rtrim($l);
				if (strstr($firstdatline, "<>")) {
					$datline_sepa = "<>";
				} else {
					$datline_sepa = ",";
					$this->dat_type = "2ch_old";
				}
				$d = explode($datline_sepa, $firstdatline);
				$this->setTtitle($d[4]);
				
				// be.2ch.net ならEUC→SJIS変換
				if (P2Util::isHostBe2chNet($this->host)) {
					$ttitle = mb_convert_encoding($this->ttitle, 'SJIS-win', 'EUC-JP');
					$this->setTtitle($ttitle);
				}
			}
			
		}
		
		return $this->ttitle;
	}

	/**
	 * ■元スレURLを返す
	 */
	function getMotoThread($ls = "")
	{
		global $_conf;

		if (P2Util::isHostMachiBbs($this->host)) {
			$motothre_url = "http://{$this->host}/bbs/read.pl?BBS={$this->bbs}&KEY={$this->key}";
		} elseif (P2Util::isHostMachiBbsNet($this->host)) {
			$motothre_url = "http://{$this->host}/test/read.cgi?bbs={$this->bbs}&key={$this->key}";	
		} elseif (P2Util::isHostJbbsShitaraba($this->host)) {
			$host_bbs_cgi = preg_replace('{(jbbs\.shitaraba\.com|jbbs\.livedoor\.com|jbbs\.livedoor\.jp)}', '$1/bbs/read.cgi', $this->host);
			$motothre_url = "http://{$host_bbs_cgi}/{$this->bbs}/{$this->key}/{$ls}";
			//$motothre_url = "http://{$this->host}/bbs/read.cgi?BBS={$this->bbs}&KEY={$this->key}";
		} elseif (P2Util::isHost2chs($this->host)) {
			if ($_conf['ktai']) {
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
		if ($pastsc = $nowtime - $this->key) {
			$this->dayres = $this->rescount / $pastsc * 60 * 60 * 24;
			return true;
		}
		return false;
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