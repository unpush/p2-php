<?php
// p2 - スレッド表示スクリプト
// フレーム分割画面、右下部分

require_once("./conf.php"); //基本設定読込
require_once("./thread_class.inc"); //スレッドクラス読込
require_once("./threadread_class.inc"); //スレッドリードクラス読込
require_once("./filectl_class.inc");
require_once("./datactl.inc");
require_once("./read.inc");
require_once("./showthread_class.inc"); //HTML表示クラス

$debug=0;
$debug && include_once("profiler.inc"); //
$debug && $prof = new Profiler( true ); //

authorize(); //ユーザ認証

//================================================================
// 変数
//================================================================

$newtime= date("gis");  //同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー
//$_today = date("y/m/d");

if($_GET['relogin2ch']){
	$relogin2ch=$_GET['relogin2ch'];
}
$_info_msg_ht = "";

//=================================================
// スレの指定
//=================================================
detectThread();

//=================================================
// レスフィルタ
//=================================================
if ($_POST['word']) { $word = $_POST['word']; }
if ($_GET['word']) { $word = $_GET['word']; }
if ($_POST['field']) { $field = $res_filter['field'] = $_POST['field']; }
if ($_GET['field']) { $field = $res_filter['field'] = $_GET['field']; }
if ($_POST['match']) { $res_filter['match'] = $_POST['match']; }
if ($_GET['match']) { $res_filter['match'] = $_GET['match']; }
if ($_POST['method']) { $res_filter['method'] = $_POST['method']; }
if ($_GET['method']) { $res_filter['method'] = $_GET['method']; }
if (get_magic_quotes_gpc()) {
	$word = stripslashes($word);
}
if ($word == '.') {$word = '';}
if (isset($word) && strlen($word) > 0) {
	if (!((!$_conf['enable_exfilter'] || $res_filter['method'] == 'regex') && preg_match('/^\.+$/', $word))) {
		include_once './strctl.class.php';
		$word_fm = StrCtl::wordForMatch($word, $res_filter['method']);
		if ($res_filter['method'] != 'just') {
			if (P2_MBREGEX_AVAILABLE == 1) {
				$words_fm = @mb_split('\s+', $word_fm);
				$word_fm = @mb_ereg_replace('\s+', '|', $word_fm);
			} else {
				$words_fm = @preg_split('/\s+/u', $word_fm);
				$word_fm = @preg_replace('/\s+/u', '|', $word_fm);
			}
		}
	}
}

//=================================================
// フィルタ値保存
//=================================================
$cachefile = $prefdir . "/p2_res_filter.txt";

if (isset($res_filter)) { // 指定があれば ファイル に保存

	FileCtl::make_datafile($cachefile, $p2_perm); //ファイルがなければ生成
	if($res_filter){$res_filter_cont=serialize($res_filter);}
	if($res_filter_cont){
		$fp = @fopen($cachefile, "wb") or die("Error: $cachefile を更新できませんでした");
		fputs($fp, $res_filter_cont);
		fclose($fp);
	}

}else{ //指定がなければ前回保存を読み込み
	$res_filter_cont = FileCtl::get_file_contents($cachefile);
	if($res_filter_cont){$res_filter=unserialize($res_filter_cont);}
}
unset($cachefile);

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
readNgAbornFile();

//==================================================================
// メイン
//==================================================================

$aThread = new ThreadRead;

//==========================================================
// idxの読み込み
//==========================================================

// hostを分解してidxファイルのパスを求める
$aThread->setThreadPathInfo($host, $bbs, $key);

// 板ディレクトリが無ければ作る
// FileCtl::mkdir_for($aThread->keyidx);

$aThread->itaj = getItaName($host, $bbs);
if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

// idxファイルがあれば読み込む
if (is_readable($aThread->keyidx)) {
	$lines = @file($aThread->keyidx);
	$data = explode('<>', rtrim($lines[0]));
}
$aThread->getThreadInfoFromIdx($aThread->keyidx);

//==========================================================
// preview >>1
//==========================================================

if ($_GET['one']) {
	$body = $aThread->previewOne();
	$ptitle_ht = $aThread->itaj." / ".$aThread->ttitle;
	include($read_header_inc);
	echo $body;
	include($read_footer_inc);
	return;
}

//===========================================================
// DATのダウンロード
//===========================================================
if (!$_GET['offline']) {
	if (!($word and file_exists($aThread->keydat))) {
		$aThread->downloadDat();
	}
}

//DATを読み込み========================================
$aThread->readDat($aThread->keydat);
$aThread->setTitleFromLocal(); //タイトルを取得して設定

//===========================================================
// 表示レス番の範囲を設定
//===========================================================
if ($ktai) {
	$before_respointer = $before_respointer_k;
}
if ($aThread->isKitoku()) { // 取得済みなら
	
	if ($_GET['nt']) { //「新着レスの表示」の時は特別にちょっと前のレスから表示
		if (substr($ls, -1) == "-") {
			$n = $ls - $before_respointer;
			if ($n<1) { $n = 1; }
			$ls = "$n-";
		}
		
	} elseif (!$ls) {
		$from_num = $aThread->newline -$respointer - $before_respointer;
		if ($from_num < 1) {
			$from_num = 1;
		} elseif ($from_num > $aThread->rescount) {
			$from_num = $aThread->rescount -$respointer - $before_respointer;
		}
		$ls = "$from_num-";
	}
	
	if ($ktai && (!strstr($ls, "n"))) {
		$ls = $ls."n";
	}
	
// 未取得なら
} else {
	if (!$ls) { $ls = $get_new_res; }
}

$aThread->lsToPoint($ls, $aThread->rescount);

//===============================================================
// プリント
//===============================================================
$ptitle_ht = $aThread->itaj." / ".$aThread->ttitle;

if($ktai){
	
	//ヘッダプリント
	include("./read_header_k.inc");
	
	if($aThread->rescount){
		include_once("./showthreadk_class.inc"); //HTML表示クラス
		$aShowThread = new ShowThreadK($aThread);
		echo $aShowThread->datToHtml();
	}
	
	//フッタプリント
	include("./read_footer_k.inc");
	
}else{
	//===========================================================
	// ヘッダ 表示
	//===========================================================
	include($read_header_inc);
	
	//===========================================================
	// ローカルDatを変換してHTML表示
	//===========================================================
	$debug && $prof->startTimer( "datToHtml" );
	
	if($aThread->rescount){
		//echo $aThread->datToHtml(); //dat を html に変換表示
		
		include_once("./showthreadpc_class.inc"); //HTML表示クラス
		$aShowThread = new ShowThreadPc($aThread);
		
		$res1 = $aShowThread->quoteOne(); //>>1ポップアップ用
		echo $res1['q'];

		echo $aShowThread->datToHtml();
	}
	
	$debug && $prof->stopTimer( "datToHtml" );
	
	//===========================================================
	// フッタ 表示
	//===========================================================
	include($read_footer_inc);
	
	$debug && $prof->printTimers( true );

}

//===========================================================
// idxの値設定
//===========================================================
if($aThread->rescount){

	if($aThread->resrange['to']+1 > $aThread->newline){
		$aThread->newline = $aThread->resrange['to']+1;
	}else{
		$aThread->newline = $data[9];
	}
	//異常値修正
	if($aThread->newline > $aThread->rescount+1){
		$aThread->newline = $aThread->rescount+1;
	}elseif($aThread->newline < 1){
		$aThread->newline = 1;
	}
	
	$s = "{$aThread->ttitle}<>{$aThread->key}<>$data[2]<>{$aThread->rescount}<>{$aThread->modified}<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>{$aThread->newline}";
	setKeyIdx($aThread->keyidx, $s); // key.idxに記録
}

//===========================================================
//履歴を記録
//===========================================================
if ($aThread->rescount) {
	$newdata = "{$aThread->ttitle}<>{$aThread->key}<>$data[2]<>{$aThread->rescount}<>{$aThread->modified}<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>{$aThread->newline}<>{$aThread->host}<>{$aThread->bbs}";
	recRecent($newdata);
}

//以上---------------------------------------------------------------
exit;



//==================================================================
// 関数
//==================================================================

/**
 * スレッドを指定する
 */
function detectThread()
{
	global $_conf, $host, $bbs, $key, $ls;
	
	if ($nama_url = $_GET['nama_url']) { // スレURLの直接指定
	
			// 2ch or pink - http://choco.2ch.net/test/read.cgi/event/1027770702/
			if( preg_match("/http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?/", $nama_url, $matches) ){
				$host=$matches[1];
				$bbs=$matches[3];
				$key=$matches[4];
				$ls=$matches[6];
				
			// 2ch or pink 過去ログhtml - http://pc.2ch.net/mac/kako/1015/10153/1015358199.html
			} elseif ( preg_match("/(http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html/", $nama_url, $matches) ){ //2ch pink 過去ログhtml
				$host=$matches[2];
				$bbs=$matches[5];
				$key=$matches[7];
				$kakolog_uri = $matches[1];
				$_GET['kakolog']= urlencode($kakolog_uri);
				
			// まち＆したらばJBBS - http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
			} elseif ( preg_match("/http:\/\/([^\/]+\.machibbs\.com|[^\/]+\.machi\.to)\/bbs\/read\.(pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(&START=([0-9]+))?(&END=([0-9]+))?[^\"]*/", $nama_url, $matches) ){
				$host=$matches[1];
				$bbs=$matches[3];
				$key=$matches[4];
				$ls=$matches[6] ."-". $matches[8];
			} elseif (preg_match("{http://((jbbs\.livedoor\.jp|jbbs\.livedoor.com|jbbs\.shitaraba\.com)(/[^/]+)?)/bbs/read\.(pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(&START=([0-9]+))?(&END=([0-9]+))?[^\"]*}", $nama_url, $matches)) {
				$host = $matches[1];
				$bbs = $matches[5];
				$key = $matches[6];
				$ls = $matches[8] ."-". $matches[10];
				
			// したらばJBBS http://jbbs.livedoor.com/bbs/read.cgi/computer/2999/1081177036/-100 
			}elseif( preg_match("{http://(jbbs\.livedoor\.jp|jbbs\.livedoor.com|jbbs\.shitaraba\.com)/bbs/read\.cgi/(\w+)/(\d+)/(\d+)/((\d+)?-(\d+)?)?[^\"]*}", $nama_url, $matches) ){
				$host = $matches[1] ."/". $matches[2];
				$bbs = $matches[3];
				$key = $matches[4];
				$ls = $matches[5];
			}
	
	}else{
		if($_GET['host']){$host = $_GET['host'];} //"pc.2ch.net"
		if($_POST['host']){$host = $_POST['host'];}
		if($_GET['bbs']){$bbs = $_GET['bbs'];} //"php"
		if($_POST['bbs']){$bbs = $_POST['bbs'];}
		if($_GET['key']){$key = $_GET['key'];} //"1022999539"
		if($_POST['key']){$key = $_POST['key'];}
		if($_GET['ls']){$ls = $_GET['ls'];} //"all"
		if($_POST['ls']){$ls = $_POST['ls'];}
	}
	
	if(!($host && $bbs && $key)){die("p2 - {$_conf['read_php']}: スレッドの指定が変です。");}
}

/**
 * 履歴を記録する
 */
function recRecent($data)
{
	global $rctfile, $rct_rec_num, $rct_perm;
	
	FileCtl::make_datafile($rctfile, $rct_perm); //$rctfileファイルがなければ生成
	
	$lines= @file($rctfile); //読み込み

	// 最初に重複要素を削除
	if ($lines) {
		foreach($lines as $line){
			$line = rtrim($line);
			$lar = explode('<>', $line);
			$data_ar = explode('<>', $data);
			if ($lar[1] == $data_ar[1]) { continue; } // keyで重複回避
			if (!$lar[1]) { continue; } // keyのないものは不正データ
			$neolines[] = $line;
		}
	}
	
	// 新規データ追加
	$neolines ? array_unshift($neolines, $data) : $neolines = array($data);

	while (sizeof($neolines) > $rct_rec_num) {
		array_pop($neolines);
	}
	
	// 書き込む
	$fp = @fopen($rctfile, "wb") or die("Error: $rctfile を更新できませんでした");
	if ($neolines) {
		foreach ($neolines as $l) {
			fputs($fp, $l."\n");
		}
	}
	fclose($fp);
}


?>