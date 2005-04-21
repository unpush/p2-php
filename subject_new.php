<?php
/*	p2 -  スレッドサブジェクト表示スクリプト
	フレーム分割画面、右上部分

	新着数を知るために使用している	// $shinchaku_num, $_newthre_num をセット

	subject.php と兄弟なので一緒に面倒をみる
*/

include_once './conf/conf.inc.php';  // 基本設定
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './threadlist.class.php'; // スレッドリスト クラス
require_once './thread.class.php';	// スレッド クラス
require_once './filectl.class.php';

$shinchaku_num = 0;
if ($aThreadList) {
	unset($aThreadList);
}


/*
$debug = false;
$debug && include_once("./profiler.inc"); //
$debug && $prof =& new Profiler(true); //

authorize(); // ユーザ認証
*/

//============================================================
// ■変数設定
//============================================================

// ホスト、板、モード設定 =================================
/*
if (!$host) {$host = $_GET['host'];}
if (!$host) {$host = $_POST['host'];}
if (!$bbs) {$bbs = $_GET['bbs'];}
if (!$bbs) {$bbs = $_POST['bbs'];}
if (!$spmode) {$spmode = $_GET['spmode'];}
if (!$spmode) {$spmode = $_POST['spmode'];}
*/

if (isset($_GET['from'])) { $sb_disp_from = $_GET['from']; }
if (isset($_POST['from'])) { $sb_disp_from = $_POST['from']; }
if (!isset($sb_disp_from)) { $sb_disp_from = 1; }

// ■ p2_setting 設定 ======================================
if ($spmode) {
	$p2_setting_txt = $_conf['pref_dir']."/p2_setting_".$spmode.".txt";
} else {
	$datdir_host = P2Util::datdirOfHost($host);
	$p2_setting_txt = $datdir_host."/".$bbs."/p2_setting.txt";
	$sb_keys_b_txt = $datdir_host."/".$bbs."/p2_sb_keys_b.txt";
	$sb_keys_txt = $datdir_host."/".$bbs."/p2_sb_keys.txt";

	if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
		if ($prepre_sb_cont = @file_get_contents($sb_keys_b_txt)) {
			$prepre_sb_keys = unserialize($prepre_sb_cont);
		}
	} else {
		if ($pre_sb_cont = @file_get_contents($sb_keys_txt)) {
			$pre_sb_keys = unserialize($pre_sb_cont);
		}
	}
		
}

// ■p2_setting 読み込み
$p2_setting_cont = @file_get_contents($p2_setting_txt);
if ($p2_setting_cont) {$p2_setting = unserialize($p2_setting_cont);}

$viewnum_pre = $p2_setting['viewnum'];
$sort_pre = $p2_setting['sort'];
$itaj_pre = $p2_setting['itaj'];

if (isset($_GET['sb_view'])) { $sb_view = $_GET['sb_view']; }
if (isset($_POST['sb_view'])) { $sb_view = $_POST['sb_view']; }
if (!$sb_view) {$sb_view = "normal";}

if (isset($_GET['viewnum'])) { $p2_setting['viewnum'] = $_GET['viewnum']; }
if (isset($_POST['viewnum'])) { $p2_setting['viewnum'] = $_POST['viewnum']; }
if (!$p2_setting['viewnum']) { $p2_setting['viewnum'] = $_conf['display_threads_num']; } // デフォルト値


if (isset($_GET['itaj_en'])) { $p2_setting['itaj'] = base64_decode($_GET['itaj_en']); }

// ■表示スレッド数 ====================================
$threads_num_max = 2000;

if (!$spmode || $spmode=="news") {
	$threads_num = $p2_setting['viewnum'];
} elseif ($spmode == "recent") {
	$threads_num = $_conf['rct_rec_num'];
} elseif ($spmode == "res_hist") {
	$threads_num = $_conf['res_hist_rec_num'];
} else {
	$threads_num = 2000;
}

if ($p2_setting['viewnum'] == "all") {$threads_num = $threads_num_max;}
elseif ($sb_view == "shinchaku") {$threads_num = $threads_num_max;}
elseif ($sb_view == "edit") {$threads_num = $threads_num_max;}
elseif ($_GET['word']) {$threads_num = $threads_num_max;}
elseif ($_conf['ktai']) {$threads_num = $threads_num_max;}

// submit ==========================================
if (isset($_GET['submit'])) {
	$submit = $_GET['submit'];
} elseif (isset($_POST['submit'])) {
	$submit = $_POST['submit'];
}

$abornoff_st = 'あぼーん解除';
$deletelog_st = 'ログを削除';

$nowtime = time();

/*
//============================================================
// ■特殊な前置処理
//============================================================

// 削除
if ($_GET['dele'] or ($_POST['submit'] == $deletelog_st)) {
	if ($host && $bbs) {
		include_once 'dele.inc.php';
		if ($_POST['checkedkeys']) {
			$dele_keys = $_POST['checkedkeys'];
		} else {
			$dele_keys = array($_GET['key']);
		}
		deleteLogs($host, $bbs, $dele_keys);
	}
}

// お気に入りスレッド
elseif (isset($_GET['setfav']) && $_GET['key'] && $host && $bbs) {
	include_once 'setfav.inc.php';
	setFav($host, $bbs, $_GET['key'], $_GET['setfav']);
}

// 殿堂入り
elseif (isset($_GET['setpal']) && $_GET['key'] && $host && $bbs) {
	include_once 'setpalace.inc.php';
	setPal($host, $bbs, $_GET['key'], $_GET['setpal']);
}

// あぼーんスレッド解除
elseif (($_POST['submit'] == $abornoff_st) && $host && $bbs && $_POST['checkedkeys']) {
	include_once 'settaborn_off.inc.php';
	settaborn_off($host, $bbs, $_POST['checkedkeys']);
}

// スレッドあぼーん
elseif (isset($_GET['taborn']) && $key && $host && $bbs) {
	include_once 'settaborn.inc.php';
	settaborn($host, $bbs, $key, $_GET['taborn']);
}

*/

//============================================================
// ■メイン
//============================================================

$aThreadList =& new ThreadList();

// ■板とモードのセット ===================================
if ($spmode) {
	if ($spmode == "taborn" or $spmode == "soko") {
		$aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
	}
	$aThreadList->setSpMode($spmode);	
} else {
	// if(!$p2_setting['itaj']){$p2_setting['itaj'] = P2Util::getItaName($host, $bbs);}
	$aThreadList->setIta($host, $bbs, $p2_setting['itaj']);
	
	// ■スレッドあぼーんリスト読込===================================
	$datdir_host = P2Util::datdirOfHost($aThreadList->host);
	$taborn_idx = $datdir_host."/".$aThreadList->bbs."/p2_threads_aborn.idx";

	$tabornlines = @file($taborn_idx);
	
	if (is_array($tabornlines)) {
		$ta_num = sizeof($tabornlines);
		foreach ($tabornlines as $l) {
			$data = explode('<>', rtrim($l));	
			$ta_keys[ $data[1] ] = true;
		}
	}

}

// ■ソースリスト読込==================================
$lines = $aThreadList->readList();

// ■お気にスレリスト 読込
$favlines = @file($_conf['favlist_file']);
if (is_array($favlines)) {
	foreach ($favlines as $l) {
		$data = explode('<>', rtrim($l));
		$fav_keys[ $data[1] ] = true;
	}
}

//============================================================
// ■それぞれの行解析
//============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize ; $x++) {

	$l = rtrim($lines[$x]);
	
	$aThread =& new Thread();
	
	if ($aThreadList->spmode != "taborn" and $aThreadList->spmode != "soko") {
		$aThread->torder = $x + 1;
	}

	// ■データ読み込み
	if ($aThreadList->spmode) {
		switch ($aThreadList->spmode) {
	    case "recent": // 履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
	        break;
	    case "res_hist": // 書き込み履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
	        break;
	    case "fav": // お気に
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
	        break;
		case "taborn":	// スレッドあぼーん
			$la = explode("<>", $l);
			$aThread->key = $la[1];
			$aThread->host = $aThreadList->host;
			$aThread->bbs = $aThreadList->bbs;	
	        break;
		case "soko":	// dat倉庫
			$la = explode("<>", $l);
			$aThread->key = $la[1];
			$aThread->host = $aThreadList->host;
			$aThread->bbs = $aThreadList->bbs;	
	        break;
		case "palace":	// スレの殿堂
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
	        break;
	    case "news": // ニュースの勢い
	        $aThread->isonline = true;
			$aThread->key = $l['key'];
			$aThread->setTtitle($l['ttitle']);
			$aThread->rescount = $l['rescount'];
			$aThread->host = $l['host'];
			$aThread->bbs = $l['bbs'];

			$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
			if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
	        break;
		}
	// subject (not spmode)
	} else {
		$aThread->getThreadInfoFromSubjectTxtLine($l);
		$aThread->host = $aThreadList->host;
		$aThread->bbs = $aThreadList->bbs;
		if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
			if (!$prepre_sb_keys[$aThread->key]) { $aThread->new = true; }
		} else {
			if (!$pre_sb_keys[$aThread->key]) { $aThread->new = true; }
			$subject_keys[$aThread->key] = true;
		}
	}

	// hostもbbsもkeyも不明ならスキップ
	if (!($aThread->host && $aThread->bbs && $aThread->key)) {
		unset($aThread);
		continue;
	}
	
	$debug && $prof->startTimer('word_filter_for_sb');
	// ■ワードフィルタ(for subject) ====================================
	if (!$aThreadList->spmode || $aThreadList->spmode=="news" and $word_fm) {
		$target = $aThread->ttitle;
		if (!StrCtl::filterMatch($word_fm, $target)) {
			unset($aThread);
			continue;
		} else {
			$mikke++;
			if ($_conf['ktai']) {
				$aThread->ttitle_ht = $aThread->ttitle;
			} else {
				$aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle);
			}
		}
	}
	$debug && $prof->stopTimer('word_filter_for_sb');
	
	// ■スレッドあぼーんチェック =====================================
	if ($aThreadList->spmode != 'taborn' and $ta_keys[$aThread->key]) { 
			unset($ta_keys[$aThread->key]);
			continue; //あぼーんスレはスキップ
	}

	$aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
	$aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得


	$debug && $prof->startTimer('favlist_check');
	// ■ favlistチェック =====================================
	// if ($x <= $threads_num) {
		if ($aThreadList->spmode != 'taborn' and $fav_keys[$aThread->key]) {
			$aThread->fav = 1;
			unset($fav_keys[$aThread->key]);
		}
	// }
	$debug && $prof->stopTimer('favlist_check');
	
	// ■ spmode(殿堂入り、newsを除く)なら ====================================
	if($aThreadList->spmode && $aThreadList->spmode!="news" && $sb_view!="edit"){ 
		
		// ■ subject.txtが未DLなら落としてデータを配列に格納
		if(! $subject_txts["$aThread->host/$aThread->bbs"]){
			$datdir_host = P2Util::datdirOfHost($aThread->host);
			$subject_url = "http://{$aThread->host}/{$aThread->bbs}/subject.txt";
			$subjectfile = "{$datdir_host}/{$aThread->bbs}/subject.txt";
			FileCtl::mkdir_for($subjectfile); // 板ディレクトリが無ければ作る
			P2Util::subjectDownload($subject_url, $subjectfile);
			
			$debug && $prof->startTimer( "subthre_read" ); //
			if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {
			
				if (extension_loaded('zlib') and strstr($aThread->host, ".2ch.net")) {
					$sblines = @gzfile($subjectfile);
				} else {
					$sblines = @file($subjectfile);
				}
				if (is_array($sblines)) {
					$it = 1;
					foreach ($sblines as $asbl) {
						if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $asbl, $matches)) {
							$akey=$matches[1];
							$subject_txts["$aThread->host/$aThread->bbs"][$akey]['ttitle'] = rtrim($matches[4]);
							$subject_txts["$aThread->host/$aThread->bbs"][$akey]['rescount'] = $matches[6];
							$subject_txts["$aThread->host/$aThread->bbs"][$akey]['torder'] = $it;
						}
						$it++;
					}
				}
				
			} else {
			
				if (extension_loaded('zlib') and strstr($aThread->host, '.2ch.net')) {
					$subject_txts["$aThread->host/$aThread->bbs"] = @gzfile($subjectfile);
				} else {
					$subject_txts["$aThread->host/$aThread->bbs"] = @file($subjectfile);
				}
				
			}
			$debug && $prof->stopTimer('subthre_read');//
		}

		$debug && $prof->startTimer('subthre_check');//
		// ■スレ情報取得 =============================
		if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {
		
			if ($subject_txts[$aThread->host.'/'.$aThread->bbs][$aThread->key]) {
			
				// 倉庫はオンラインを含まない
				if ($aThreadList->spmode == "soko") {
					unset($aThread);
					continue;
				} elseif ($aThreadList->spmode == "taborn") {
					// subject.txt からスレ情報取得
					// $aThread->getThreadInfoFromSubjectTxtLine($l);
					$aThread->isonline = true;
					$ttitle = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['ttitle'];
					$aThread->setTtitle($ttitle);
					$aThread->rescount = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['rescount'];
					if ($aThread->readnum) {
						$aThread->unum = $aThread->rescount - $aThread->readnum;
						// machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
						if ($aThread->unum < 0) { $aThread->unum = 0; }
					}
					$aThread->torder = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['torder'];
				}

			}
			
		} else {
		
			if ($subject_txts[$aThread->host.'/'.$aThread->bbs]) {
				$it = 1;
				foreach ($subject_txts[$aThread->host.'/'.$aThread->bbs] as $l) {
					if (@preg_match("/^{$aThread->key}/", $l)) {
						// subject.txt からスレ情報取得
						$aThread->getThreadInfoFromSubjectTxtLine($l);
						break;
					}
					$it++;
				}
			}
		
		}
		$debug && $prof->stopTimer('subthre_check'); //
		
		if ($aThreadList->spmode == 'taborn') {
			if (!$aThread->torder) { $aThread->torder = '-'; }
		}

		
		// ■新着のみ(for spmode) ===============================
		if ($sb_view == 'shinchaku' and !$_GET['word']) { 
			if ($aThread->unum < 1) {
				unset($aThread);
				continue;
			}
		}
		
		/*
		// ■ワードフィルタ(for spmode) ==================================
		if ($word_fm) {
			$target = $aThread->ttitle;
			if (!StrCtl::filterMatch($word_fm, $target)) {
				unset($aThread);
				continue;
			} else {
				$mikke++;
				if ($_conf['ktai']) {
					$aThread->ttitle_ht = $aThread->ttitle;
				} else {
					$aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle);
				}
			}
		}
		*/
	}
	
	// subjexctからrescountが取れなかった場合は、gotnumを利用する。
	if ((!$aThread->rescount) and $aThread->gotnum) {
		$aThread->rescount = $aThread->gotnum;
	}
	if (!$aThread->ttitle_ht) {
		$aThread->ttitle_ht = $aThread->ttitle;
	}
	
	if ($aThread->unum > 0) { // 新着あり
		$shinchaku_attayo = true;
		$shinchaku_num = $shinchaku_num + $aThread->unum; // 新着数set
	} elseif ($aThread->fav) { // お気にスレ
		;
	} elseif ($aThread->new) { // 新規スレ
		$_newthre_num++; // ※showbrdmenupc.class.php
	} else {
		// 携帯とニュースチェック以外で
		if ($_conf['ktai'] or $spmode != "news") {
			// 指定数を越えていたらカット
			if($x >= $threads_num){
				unset($aThread);
				continue;
			}
		}
	}
	
	/*
	// ■新着ソートの便宜上 unum をセット調整
	if (!isset($aThread->unum)) {
		if ($aThreadList->spmode == "recent" or $aThreadList->spmode == "res_hist" or $aThreadList->spmode == "taborn") {
			$aThread->unum = -0.1;
		} else {
			$aThread->unum = $_conf['sort_zero_adjust'];
		}
	}
	*/
	
	// 勢いのセット
	$aThread->setDayRes($nowtime);
	
	/*
	// 生存数set
	if ($aThread->isonline) { $online_num++; }
	
	// ■リストに追加 ==============================================
	$aThreadList->addThread($aThread);

	*/
	unset($aThread);
}

// $shinchaku_num

/*
// 既にdat落ちしているスレは自動的にあぼーんを解除する =========================
if (!$aThreadList->spmode and !$word and $aThreadList->threads and $ta_keys) {
	include_once 'settaborn_off.inc.php';
	//echo sizeof($ta_keys)."*<br>";
	$ta_vkeys = array_keys($ta_keys);
	settaborn_off($aThreadList->host, $aThreadList->bbs, $ta_vkeys);
	foreach ($ta_vkeys as $k) {
		$ta_num--;
		if ($k) {
			$ks .= "key:$k ";
		}
	}
	$ks && $_info_msg_ht .= "<div class=\"info\">　p2 info: DAT落ちしたスレッドあぼーんを自動解除しました - $ks</div>";
}


//============================================================
// $subject_keys をシリアライズして保存
//============================================================
//if(file_exists($sb_keys_b_txt)){ unlink($sb_keys_b_txt); }
if($subject_keys){
	if(file_exists($sb_keys_txt)){
		copy($sb_keys_txt, $sb_keys_b_txt);
	}else{
		FileCtl::make_datafile($sb_keys_txt, $_conf['p2_perm']);
	}
	if($subject_keys){$sb_keys_cont = serialize($subject_keys);}
	if($sb_keys_cont){
		$fp = fopen($sb_keys_txt, "wb") or die("Error: $sb_keys_txt を更新できませんでした");
		@flock($fp, LOCK_EX);
		fputs($fp, $sb_keys_cont);
		@flock($fp, LOCK_UN);
		fclose($fp);
	}
}

*/
?>