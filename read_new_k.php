<?php
// p2 - スレッド表示スクリプト - 新着まとめ読み（携帯）
// フレーム分割画面、右下部分

require_once("./conf.php"); // 設定
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once("threadlist_class.inc"); // スレッドリスト クラス
require_once("thread_class.inc"); //スレッド クラス
require_once("threadread_class.inc"); //スレッドリード クラス
require_once("datactl.inc");
require_once("read.inc");

authorize(); //ユーザ認証

//==================================================================
// 変数
//==================================================================
$GLOBALS['rnum_all_range'] = $_conf['k_rnum_range'];

$sb_view="shinchaku";
$newtime= date("gis");
$_info_msg_ht="";

//=================================================
// 板の指定
//=================================================

if($_GET['host']){$host = $_GET['host'];}
if($_POST['host']){$host = $_POST['host'];}
if($_GET['bbs']){$bbs = $_GET['bbs'];}
if($_POST['bbs']){$bbs = $_POST['bbs'];}
if(! $spmode){$spmode = $_GET['spmode'];}
if(! $spmode){$spmode = $_POST['spmode'];}

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
readNgAbornFile();

//====================================================================
// メイン
//====================================================================

$aThreadList = new ThreadList;

//板とモードのセット===================================
if($spmode){
	if($spmode=="taborn" or $spmode=="soko"){
		$aThreadList->setIta($host, $bbs, getItaName($host, $bbs));
	}
	$aThreadList->setSpMode($spmode);	
}else{
	$aThreadList->setIta($host, $bbs, getItaName($host, $bbs));

	//スレッドあぼーんリスト読込
	$datdir_host = datdirOfHost($host);
	$tabornlines = @file($datdir_host."/".$bbs."/p2_threads_aborn.idx");
	if ($tabornlines) {
		$ta_num = sizeOf($tabornlines);
		foreach ($tabornlines as $l) {
			$tarray = explode('<>', rtrim($l));
			$ta_keys[ $tarray[1] ] = true;
		}
	}
}

//ソースリスト読込==================================
$lines = $aThreadList->readList();

//ページヘッダ表示===================================
$ptitle_ht="{$aThreadList->ptitle} の 新着まとめ読み";

//&amp;sb_view={$sb_view}
if($aThreadList->spmode){
	$sb_ht =<<<EOP
		<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$k_at_a}">{$aThreadList->ptitle}</a>
EOP;
	$sb_ht_btm =<<<EOP
		<a {$accesskey}="{$k_accesskey['above']}" href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$k_at_a}">{$k_accesskey['above']}.{$aThreadList->ptitle}</a>
EOP;
}else{
	$sb_ht =<<<EOP
		<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$k_at_a}">{$aThreadList->ptitle}</a>
EOP;
	$sb_ht_btm =<<<EOP
		<a {$accesskey}="{$k_accesskey['above']}" href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$k_at_a}">{$k_accesskey['above']}.{$aThreadList->ptitle}</a>
EOP;
}

//include($read_header_inc);

header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOHEADER
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle_ht}</title>
EOHEADER;

echo <<<EOP
</head>
<body>\n
EOP;

echo "<p>{$sb_ht}の新まとめ</p>";

echo $_info_msg_ht;
$_info_msg_ht="";

//==============================================================
// それぞれの行解析
//==============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize ; $x++) {

	if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
		break;
	}

	$l=$lines[$x];
	$aThread = new ThreadRead;
	
	$aThread->torder=$x+1;

	//データ読み込み
	if($aThreadList->spmode){
		switch ($aThreadList->spmode) {
	    case "recent": //履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
	    case "res_hist": //書き込み履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
	    case "fav": //お気に
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
		case "taborn":
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->host = $aThreadList->host;
			$aThread->bbs = $aThreadList->bbs;
	        break;
		case "palace":
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
		}
	}else{// subject (not spmode)
		$aThread->getThreadInfoFromSubjectTxtLine($l);
		$aThread->host = $aThreadList->host;
		$aThread->bbs = $aThreadList->bbs;
	}
	
	if(!($aThread->host && $aThread->bbs)){unset($aThread); continue;} //hostもbbsも不明ならスキップ
	
	$aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
	$aThread->getThreadInfoFromIdx($aThread->keyidx); //既得スレッドデータをidxから取得

	// 新着のみ(for subject) =========================================
	if(! $aThreadList->spmode and $sb_view=="shinchaku" and ! $_GET['word']){ 
		if($aThread->unum < 1){unset($aThread); continue;}
	}

	//スレッドあぼーんチェック =====================================
	if($aThreadList->spmode != "taborn" and $ta_keys[$aThread->key]){ 
			unset($ta_keys[$aThread->key]);
			continue; //あぼーんスレはスキップ
	}

	// spmode(殿堂入りを除く)なら	====================================
	if($aThreadList->spmode && $sb_view!="edit"){ 
		
		// subject.txtが未DLなら落としてデータを配列に格納
		if(! $subject_txts["$aThread->host/$aThread->bbs"]){
			$datdir_host=datdirOfHost($aThread->host);
			$subject_url="http://{$aThread->host}/{$aThread->bbs}/subject.txt";
			$subjectfile="{$datdir_host}/{$aThread->bbs}/subject.txt";
			FileCtl::mkdir_for($subjectfile); //板ディレクトリが無ければ作る
			if(! ($word_fm and file_exists($subjectfile)) ){
				P2Util::subjectDownload($subject_url, $subjectfile);
			}
			if(extension_loaded('zlib') and strstr($aThread->host, ".2ch.net")){
				$subject_txts["$aThread->host/$aThread->bbs"] = @gzfile($subjectfile);
			}else{
				$subject_txts["$aThread->host/$aThread->bbs"] = @file($subjectfile);
			}
			
		}
		
		// スレ情報取得 =============================
		if($subject_txts["$aThread->host/$aThread->bbs"]){
			foreach($subject_txts["$aThread->host/$aThread->bbs"] as $l){
				if( @preg_match("/^{$aThread->key}/",$l) ){
					$aThread->getThreadInfoFromSubjectTxtLine($l); //subject.txt からスレ情報取得
					break;
				}
			}
		}
		
		// 新着のみ(for spmode) ===============================
		if($sb_view=="shinchaku" and ! $_GET['word']){ 
			if($aThread->unum < 1){unset($aThread); continue;}
		}
	}
	
	if(!$aThread->ttitle_ht){$aThread->ttitle_ht=$aThread->ttitle;}
 	if($aThread->isonline){$online_num++;}//生存数set
	
	echo $_info_msg_ht;
	$_info_msg_ht="";
	
	readNew($aThread);
	
	// リストに追加 ========================================
	//$aThreadList->addThread($aThread);
	$aThreadList->num++;
	unset($aThread);
}

//$aThread = new ThreadRead;

//==================================================================

function readNew(&$aThread)
{
	global $_conf, $newthre_num, $STYLE, $browser;
	global $_info_msg_ht, $newres_to_show, $pointer_at, $spmode, $k_accesskey, $k_at_a;

	$newthre_num++;
	
	//==========================================================
	// idxの読み込み
	//==========================================================
	
	//hostを分解してidxファイルのパスを求める
	$aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
	
	//FileCtl::mkdir_for($aThread->keyidx);	 //板ディレクトリが無ければ作る //この操作はおそらく不要

	$aThread->itaj = getItaName($aThread->host, $aThread->bbs);
	if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

	// idxファイルがあれば読み込む
	if (is_readable($aThread->keyidx)) {
		$lines = @file($aThread->keyidx);
		$data = explode('<>', rtrim($lines[0]));
	}
	$aThread->getThreadInfoFromIdx($aThread->keyidx);
	
	//==================================================================
	// DATのダウンロード
	//==================================================================
	if(! ($word and file_exists($aThread->keydat)) ){
		$aThread->downloadDat();
	}
	
	// DATを読み込み
	$aThread->readDat($aThread->keydat);
	$aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定
	
	//===========================================================
	// 表示レス番の範囲を設定
	//===========================================================
	if ($aThread->isKitoku()) { // 取得済みなら
		$from_num = $aThread->readnum +1 - $_conf['respointer'] - $_conf['before_respointer_new'];
		if($from_num < 1){
			$from_num = 1;
		}elseif($from_num > $aThread->rescount){
			$from_num = $aThread->rescount - $_conf['respointer'] - $_conf['before_respointer_new'];
		}

		//if (!$ls) {
			$ls = "$from_num-";
		//}
	}
	
	$aThread->lsToPoint($ls);
	
	//==================================================================
	// ヘッダ 表示
	//==================================================================
	$motothre_url = $aThread->getMotoThread($GLOBALS['ls']);
	
	$ttitle_en = base64_encode($aThread->ttitle);
	$ttitle_en_q = "&amp;ttitle_en=".$ttitle_en;
	$bbs_q = "&amp;bbs=".$aThread->bbs;
	$key_q = "&amp;key=".$aThread->key;
	$popup_q = "&amp;popup=1";
	
	//include($read_header_inc);
	
	$prev_thre_num = $newthre_num-1;
	$next_thre_num = $newthre_num+1;
	if($prev_thre_num != 0){
		$prev_thre_ht = "<a href=\"#ntt{$prev_thre_num}\">▲</a>";
	}
	//$next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a>	";
	$next_thre_ht = "<a href=\"#ntt_bt{$newthre_num}\">▼</a>	";
	
	if($spmode){
		$read_header_itaj_ht = " ({$aThread->itaj})";
	}
	
	echo $_info_msg_ht;
	$_info_msg_ht="";
	
	$read_header_ht = <<<EOP
		<hr>
		<p {$pointer_at}="ntt{$newthre_num}"><b>{$aThread->ttitle}</b>{$read_header_itaj_ht} {$next_thre_ht}</p>
		<hr>
EOP;

	//==================================================================
	// ローカルDatを読み込んでHTML表示
	//==================================================================
	$aThread->resrange['nofirst']=true;
	$newres_to_show=false;
	if($aThread->rescount){
		//$aThread->datToHtml(); //dat を html に変換表示
		include_once("./showthread_class.inc"); //HTML表示クラス
		include_once("./showthreadk_class.inc"); //HTML表示クラス
		$aShowThread = new ShowThreadK($aThread);

		$read_cont_ht .= $aShowThread->datToHtml();
		unset($aShowThread);
	}
	
	//==================================================================
	// フッタ 表示
	//==================================================================
	//include($read_footer_inc);
	
	//----------------------------------------------
	// $read_footer_navi_new  続きを読む 新着レスの表示
	$newtime= date("gis");  //リンクをクリックしても再読込しない仕様に対抗するダミークエリー
	
	$info_st="情";
	$delete_st="削";
	$prev_st="前";
	$next_st="次";

	//表示範囲
	if($aThread->resrange['start']==$aThread->resrange['to']){
		$read_range_on=$aThread->resrange['start'];
	}else{
		$read_range_on="{$aThread->resrange['start']}-{$aThread->resrange['to']}";
	}
	$read_range_ht=<<<EOP
	{$read_range_on}/{$aThread->rescount}<br>
EOP;

	$read_footer_navi_new="<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-&amp;nt={$newtime}{$k_at_a}#r{$aThread->rescount}\">新着ﾚｽの表示</a>";
	
	$dores_ht=<<<EOP
		<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}{$k_at_a}">ﾚｽ</a>
EOP;

	//ツールバー部分HTML=======
	if ($spmode) {
		$toolbar_itaj_ht = <<<EOP
(<a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$k_at_a}">{$aThread->itaj}</a>)
EOP;
	}
	$toolbar_right_ht .=<<<EOTOOLBAR
			<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$k_at_a}">{$info_st}</a> 
			<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=true{$k_at_a}">{$delete_st}</a> 
			<a href="{$motothre_url}">元ｽﾚ</a>
EOTOOLBAR;

	$read_footer_ht = <<<EOP
		<div {$pointer_at}="ntt_bt{$newthre_num}">
			$read_range_ht 
			<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$k_at_a}#r{$aThread->rescount}">{$aThread->ttitle}</a>{$toolbar_itaj_ht} 
			<a href="#ntt{$newthre_num}">▲</a>
		</div>
		<hr>
EOP;

	//透明あぼーんで表示がない場合はスキップ
	if ($newres_to_show) {
		echo $read_header_ht;
		echo $read_cont_ht;
		echo $read_footer_ht;
	}

	//==================================================================
	// key.idxの値設定
	//==================================================================
	if ($aThread->rescount) {
	
		$aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));
		
		$newline = $aThread->readnum + 1;	// $newlineは廃止予定だが、旧互換用に念のため
		
		$s = "{$aThread->ttitle}<>{$aThread->key}<>$data[2]<>{$aThread->rescount}<>{$aThread->modified}<>{$aThread->readnum}<>$data[6]<>$data[7]<>$data[8]<>{$newline}";
		setKeyIdx($aThread->keyidx, $s); // key.idxに記録
	}

}

//==================================================================
// ページフッタ表示
//==================================================================
$newthre_num++;

if (!$aThreadList->num) {
	echo "新着ﾚｽはないぽ";
	echo "<hr>";
}

if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0) {
	echo <<<EOP
	<div>
		{$sb_ht_btm}の<a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}{$k_at_a}" {$accesskey}="{$k_accesskey['next']}">{$k_accesskey['next']}.新まとめを更新</a>
	</div>\n
EOP;
} else {
	echo <<<EOP
	<div>
		{$sb_ht_btm}の<a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}&amp;norefresh=1{$k_at_a}" {$accesskey}="{$k_accesskey['next']}">{$k_accesskey['next']}.新まとめの続き</a>
	</div>\n
EOP;
}

echo <<<EOP
<hr>
{$k_to_index_ht}
EOP;

echo <<<EOP
</body>
</html>
EOP;

?>