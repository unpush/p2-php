<?php
// p2 - スレッドリストクラス

require_once './p2util.class.php';	// p2用のユーティリティクラス

//=============================================================================
// ThreadList クラス
//=============================================================================

class ThreadList{
	var $threads; //クラスThreadのオブジェクトを格納する配列
	var $num; //格納されたThreadオブジェクトの数
	var $host; // ex)pc.2ch.net
	var $bbs; // ex)mac
	var $itaj; //板名 ex)新・mac板
	var $spmode; //普通板以外のスペシャルモード
	var $ptitle; //ページタイトル
	
	/**
	 * コンストラクタ
	 */
	function ThreadList()
	{
		$this->num=0;
	}
	
	//==============================================
	function setSpMode($name)
	{
		global $ktai;
		if($name=="recent"){
			$this->spmode=$name;
			$ktai?$this->ptitle="最近読んだｽﾚ":$this->ptitle="最近読んだスレ";
		}elseif($name=="res_hist"){
			$this->spmode=$name;
			$this->ptitle="書き込み履歴";
		}elseif($name=="fav"){
			$this->spmode=$name;
			$ktai?$this->ptitle="お気にｽﾚ":$this->ptitle="お気にスレ";
		}elseif($name=="taborn"){
			$this->spmode=$name;
			$ktai?$this->ptitle="$this->itaj (ｱﾎﾞﾝ中)":$this->ptitle="$this->itaj (あぼーん中)";
		}elseif($name=="soko"){
			$this->spmode=$name;
			$this->ptitle="$this->itaj (dat倉庫)";
		}elseif($name=="palace"){
			$this->spmode=$name;
			$ktai?$this->ptitle="ｽﾚの殿堂":$this->ptitle="スレの殿堂";
		}elseif($name=="news"){
			$this->spmode=$name;
			$ktai?$this->ptitle="ﾆｭｰｽﾁｪｯｸ":$this->ptitle="ニュースチェック";
		}
	}
	
	//==============================================
	// setIta -- 板情報をセットするメソッド
	//==============================================
	function setIta($host, $bbs, $itaj=""){
		$this->host = $host;
		$this->bbs = $bbs;
		if(!$this->itaj){
			if($itaj){
				$this->itaj = $itaj;
			}else{
				$this->itaj = $bbs;
			}
		}
		$this->ptitle = $this->itaj;
	}
	
	/**
	 * ■ readList メソッド
	 */
	function readList()
	{
		global $rctfile, $favlistfile, $datdir, $prefdir, $word_fm, $debug, $prof, $_info_msg_ht;
		
		if ($this->spmode) {
		
			// ローカルの履歴ファイル 読み込み
			if ($this->spmode == "recent") {
				if ($lines = @file($rctfile)) {
					//$_info_msg_ht = "<p>履歴は空っぽです</p>";
					//return false;
				}
			
			// ローカルの書き込み履歴ファイル 読み込み
			} elseif ($this->spmode == "res_hist") {
				$rh_idx = $prefdir."/p2_res_hist.idx";
				if ($lines = @file($rh_idx)) {
					//$_info_msg_ht = "<p>書き込み履歴は空っぽです</p>";
					//return false;
				}
			
			//ローカルのお気にファイル 読み込み
			} elseif ($this->spmode == "fav") {
				if ($lines = @file($favlistfile)) {
					//$_info_msg_ht = "<p>お気にスレは空っぽです</p>";
					//return false;
				}
			
			// ニュース系サブジェクト読み込み
			} elseif ($this->spmode == "news") {
			
				unset($news);
				$news[] = array(host=>"news2.2ch.net", bbs=>"newsplus"); // ニュース速報+
				$news[] = array(host=>"news2.2ch.net", bbs=>"liveplus"); // ニュース実況
				$news[] = array(host=>"book.2ch.net", bbs=>"bizplus"); // ビジネスニュース速報+
				$news[] = array(host=>"live2.2ch.net", bbs=>"news"); // ニュース速報
				$news[] = array(host=>"news3.2ch.net", bbs=>"news2"); // ニュース議論
				
				foreach ($news as $n) {

					$datdir_host = datdirOfHost($n['host']);
					$subject_url = "http://".$n['host']."/".$n['bbs']."/subject.txt";
					$subjectfile = $datdir_host."/".$n['bbs']."/subject.txt";
			
					FileCtl::mkdir_for($subjectfile); // 板ディレクトリが無ければ作る
		
					P2Util::subjectDownload($subject_url, $subjectfile);
					
					if (extension_loaded('zlib') and strstr($n['host'], ".2ch.net")) {
						$slines = @gzfile($subjectfile);
					} else {
						$slines = @file($subjectfile);
					}
					if ($slines) {
						foreach ($slines as $l) {
							if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches)) {
								//$this->isonline = true;
								unset($al);
								$al['key'] = $matches[1];
								$al['ttitle'] = rtrim($matches[4]);
								$al['rescount'] = $matches[6];
								$al['host'] = $n['host'];
								$al['bbs'] = $n['bbs'];
								$lines[] = $al;
							}
						}
					}
				}
		
			// p2_threads_aborn.idx 読み込み
			} elseif ($this->spmode=="taborn") {
				$datdir_host = datdirOfHost($this->host);
				$lines = @file($datdir_host."/".$this->bbs."/p2_threads_aborn.idx");
			
			// dat倉庫 ======================
			} elseif ($this->spmode == "soko") {

				$itadir = datdirOfHost($this->host)."/".$this->bbs;
				$lines = array();
				
				$debug && $prof->startTimer( "dat" );//
				// ログディレクトリを走査して孤立datにidx付加 =================
				if ($cdir = dir($itadir)) { // or die ("ログディレクトリがないよ！");
					// ディレクトリ走査
					while ($entry=$cdir->read()) {
						if (preg_match("/([0-9]+)\.dat$/",$entry, $matches)) {
							$theidx = $itadir."/".$matches[1].".idx";
							if (!file_exists($theidx)) {
								if ($datlines = @file($itadir."/".$entry)) {
									$firstdatline = rtrim($datlines[0]);
									if (strstr($firstdatline, "<>")) {
										$datline_sepa = "<>";
									} else {
										$datline_sepa = ",";
									}
									$d = explode($datline_sepa, $firstdatline);
									$atitle = $d[4];
									$arnum = sizeof($datlines);
									$anewline = $arnum;
									$data = "$atitle<>$matches[1]<><>$arnum<><><><><><>$anewline\n";
									setKeyIdx($theidx, $data);
								}
							}
							// array_push($lines, $idl[0]);
						}
					}
					$cdir->close();
				}			
				$debug && $prof->stopTimer( "dat" );//
				
				$debug && $prof->startTimer( "idx" );//
				//ログディレクトリを走査してidx情報を抽出してリスト化===========
				if ($cdir = dir($itadir)) { // or die ("ログディレクトリがないよ！");
					//ディレクトリ走査
					while ($entry = $cdir->read()) {
						if (preg_match("/\.idx$/", $entry)) {
							$idl = @file($itadir."/".$entry);
							array_push($lines, $idl[0]);
						}
					}
					$cdir->close();
				}
				$debug && $prof->stopTimer( "idx" );//
				
			} elseif ($this->spmode == "palace") { // p2_palace.idx 読み込み
				$palace_idx = $prefdir. '/p2_palace.idx';
				if ($lines = @file($palace_idx)) {
					//$_info_msg_ht = "<p>殿堂はがらんどうです</p>";
					//return false;
				}
			}
		
		// オンライン上の subject.txt を読み込む（ノーマル板モード）
		} else {
			
			$datdir_host = datdirOfHost($this->host);
			$subject_url = "http://".$this->host."/".$this->bbs."/subject.txt";
			$subjectfile = $datdir_host."/".$this->bbs."/subject.txt";
	
			FileCtl::mkdir_for($subjectfile); // 板ディレクトリが無ければ作る

			// subjectダウンロード
			P2Util::subjectDownload($subject_url, $subjectfile);
			
			if (extension_loaded('zlib') and strstr($this->host, ".2ch.net")) {
				$lines = @gzfile($subjectfile);
			} else {
				$lines = @file($subjectfile);
			}
			
			// JBBS@したらばなら重複スレタイを削除する
			if (P2Util::isHostJbbsShitaraba($this->host)) {
				$lines = array_unique($lines);
			}
			
			// be.2ch.net ならEUC→SJIS変換
			if (P2Util::isHostBe2chNet($this->host)) {
				include_once './strctl.class.php';
				$lines = array_map(create_function('$str', 'return StrCtl::p2EUCtoSJIS($str);'), $lines);
			}
			
		}
		return $lines;
	}
	
	/**
	 * ■ addThread メソッド
	 */
	function addThread($aThread)
	{
		$this->threads[] = $aThread;
		$this->num++;
		return $this->num;
	}

}

?>