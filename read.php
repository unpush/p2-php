<?php
/*
	p2 - スレッド表示スクリプト
	フレーム分割画面、右下部分
*/

include_once './conf.inc.php'; // 基本設定読込
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './thread.class.php'; // スレッドクラス読込
require_once './threadread.class.php'; // スレッドリードクラス読込
require_once './filectl.class.php';
require_once './ngabornctl.class.php';
require_once './showthread.class.php'; // HTML表示クラス

$debug = 0;
$debug && include_once("profiler.inc"); //
$debug && $prof = new Profiler(true); //

authorize(); // ユーザ認証

//================================================================
// 変数
//================================================================
$newtime = date('gis');  // 同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー
// $_today = date('y/m/d');

$_info_msg_ht = "";

//=================================================
// スレの指定
//=================================================
detectThread();	// global $host, $bbs, $key, $ls

//=================================================
// レスフィルタ
//=================================================
if (isset($_POST['word'])) { $word = $_POST['word']; }
if (isset($_GET['word'])) { $word = $_GET['word']; }
if (isset($_POST['field'])) { $res_filter['field'] = $_POST['field']; }
if (isset($_GET['field'])) { $res_filter['field'] = $_GET['field']; }
if (isset($_POST['match'])) { $res_filter['match'] = $_POST['match']; }
if (isset($_GET['match'])) { $res_filter['match'] = $_GET['match']; }
if (isset($_POST['method'])) { $res_filter['method'] = $_POST['method']; }
if (isset($_GET['method'])) { $res_filter['method'] = $_GET['method']; }

if (isset($word) && strlen($word) > 0) {

	// デフォルトオプション
	if (!$res_filter['field']) { $res_filter['field'] = "hole"; }
	if (!$res_filter['match']) { $res_filter['match'] = "on"; }
	if (!$res_filter['method']) { $res_filter['method'] = "or"; }

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
$cachefile = $_conf['pref_dir'] . '/p2_res_filter.txt';

// フィルタ指定がなければ前回保存を読み込む（フォームのデフォルト値で利用）
if (!isset($GLOBALS['word'])) {

	if ($res_filter_cont = @file_get_contents($cachefile)) {
		$res_filter = unserialize($res_filter_cont);
	}
	
// フィルタ指定があれば
} else {

	// ボタンが押されていたなら、ファイルに設定を保存
	if (isset($_REQUEST['submit_filter'])) {	// !isset($_REQUEST['idpopup'])
		FileCtl::make_datafile($cachefile, $_conf['p2_perm']); // ファイルがなければ生成
		if ($res_filter) {
			$res_filter_cont = serialize($res_filter);
		}
		if ($res_filter_cont && !$popup_filter) {
			$fp = @fopen($cachefile, 'wb') or die("Error: $cachefile を更新できませんでした");
			@flock($fp, LOCK_EX);
			fputs($fp, $res_filter_cont);
			@flock($fp, LOCK_UN);
			fclose($fp);
		}
	}
}


//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//==================================================================
// ■メイン
//==================================================================

if (!isset($aThread)) {
	$aThread = new ThreadRead;
}

//==========================================================
// idxの読み込み
//==========================================================

// hostを分解してidxファイルのパスを求める
if (!isset($aThread->keyidx)) {
	$aThread->setThreadPathInfo($host, $bbs, $key);
}

// 板ディレクトリが無ければ作る
// FileCtl::mkdir_for($aThread->keyidx);

$aThread->itaj = P2Util::getItaName($host, $bbs);
if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

// idxファイルがあれば読み込む
if (is_readable($aThread->keyidx)) {
	$lines = @file($aThread->keyidx);
	$data = explode('<>', rtrim($lines[0]));
}
$aThread->getThreadInfoFromIdx();

//==========================================================
// preview >>1
//==========================================================

if ($_GET['one']) {
	$body = $aThread->previewOne();
	$ptitle_ht = htmlspecialchars($aThread->itaj)." / ".$aThread->ttitle_hd;
	include($read_header_inc);
	echo $body;
	include($read_footer_inc);
	return;
}

//===========================================================
// DATのダウンロード
//===========================================================
if (empty($_GET['offline'])) {
	$aThread->downloadDat();
}

// ■DATを読み込み
$aThread->readDat();

// オフライン指定でもログがなければ、改めて強制読み込み
if (empty($aThread->datlines) && !empty($_GET['offline'])) {
	$aThread->downloadDat();
	$aThread->readDat();
}


$aThread->setTitleFromLocal(); // タイトルを取得して設定

//===========================================================
// 表示レス番の範囲を設定
//===========================================================
if ($_conf['ktai']) {
	$before_respointer = $_conf['before_respointer_k'];
} else {
	$before_respointer = $_conf['before_respointer'];
}

// 取得済みなら
if ($aThread->isKitoku()) {
	
	//「新着レスの表示」の時は特別にちょっと前のレスから表示
	if ($_GET['nt']) {
		if (substr($ls, -1) == "-") {
			$n = $ls - $before_respointer;
			if ($n<1) { $n = 1; }
			$ls = "$n-";
		}
		
	} elseif (!$ls) {
		$from_num = $aThread->readnum +1 - $_conf['respointer'] - $before_respointer;
		if ($from_num < 1) {
			$from_num = 1;
		} elseif ($from_num > $aThread->rescount) {
			$from_num = $aThread->rescount - $_conf['respointer'] - $before_respointer;
		}
		$ls = "$from_num-";
	}
	
	if ($_conf['ktai'] && (!strstr($ls, "n"))) {
		$ls = $ls."n";
	}
	
// 未取得なら
} else {
	if (!$ls) { $ls = $_conf['get_new_res_l']; }
}

// フィルタリングの時は、all固定とする
if (isset($word)) {
	$ls = 'all';
}

$aThread->lsToPoint($ls);

//===============================================================
// ■プリント
//===============================================================
$ptitle_ht = htmlspecialchars($aThread->itaj)." / ".$aThread->ttitle_hd;

if ($_conf['ktai']) {
	
	// ■ヘッダプリント
	include './read_header_k.inc.php';
	
	if ($aThread->rescount) {
		include_once './showthreadk.class.php'; // HTML表示クラス
		$aShowThread = new ShowThreadK($aThread);
		$aShowThread->datToHtml();
	}
	
	// ■フッタプリント
	include './read_footer_k.inc.php';
	
} else {

	// ■ヘッダ 表示
	include './read_header.inc.php';
	flush();
	
	//===========================================================
	// ローカルDatを変換してHTML表示
	//===========================================================
	// レスがあり、検索指定があれば
	if (isset($word) && $aThread->rescount) {
	
		$all = $aThread->rescount;
		
		$GLOBALS['filter_hits'] = 0;
		
		$hits_line = "<p><b id=\"filerstart\">{$all}レス中 <span id=\"searching\">{$GLOBALS['filter_hits']}</span>レスがヒット</b></p>";
		echo <<<EOP
<script type="text/javascript">
<!--
document.writeln('{$hits_line}');
var searching = document.getElementById('searching');

function filterCount(n){
	if (searching) {
		searching.innerHTML = n;
	}
}
-->
</script>
EOP;
	}
	
	$debug && $prof->startTimer("datToHtml");
	
	if ($aThread->rescount) {

		include_once './showthreadpc.class.php'; // HTML表示クラス
		$aShowThread = new ShowThreadPc($aThread);
		
		$res1 = $aShowThread->quoteOne(); // >>1ポップアップ用
		echo $res1['q'];

		$aShowThread->datToHtml();
	}
	
	$debug && $prof->stopTimer("datToHtml");
	
	// フィルタ結果を表示
	if ($word && $aThread->rescount) {
		echo <<<EOP
<script type="text/javascript">
<!--
var filerstart = document.getElementById('filerstart');
if (filerstart) {
	filerstart.style.backgroundColor = 'yellow';
	filerstart.style.fontWeight = 'bold';
}
-->
</script>\n
EOP;
		if ($GLOBALS['filter_hits'] > 5) {
			echo "<p><b class=\"filtering\">{$all}レス中 {$GLOBALS['filter_hits']}レスがヒット</b></p>\n";
		}
	}
	
	// ■フッタ 表示
	include 'read_footer.inc.php';
	
	$debug && $prof->printTimers( true );

}

//===========================================================
// idxの値を設定、記録
//===========================================================
if ($aThread->rescount) {

	$aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to'])); 
	
	$newline = $aThread->readnum + 1;	// $newlineは廃止予定だが、旧互換用に念のため

	$s = "{$aThread->ttitle}<>{$aThread->key}<>$data[2]<>{$aThread->rescount}<>{$aThread->modified}<>{$aThread->readnum}<>$data[6]<>$data[7]<>$data[8]<>{$newline}";
	P2Util::recKeyIdx($aThread->keyidx, $s); // key.idxに記録
}

//===========================================================
// 履歴を記録
//===========================================================
if ($aThread->rescount) {
	$newdata = "{$aThread->ttitle}<>{$aThread->key}<>$data[2]<><><>{$aThread->readnum}<>$data[6]<>$data[7]<>$data[8]<>{$newline}<>{$aThread->host}<>{$aThread->bbs}";
	recRecent($newdata);
}

// ■NGあぼーんを記録
NgAbornCtl::saveNgAborns();

// ■以上 ---------------------------------------------------------------
exit;



//===============================================================================
// ■関数
//===============================================================================

/**
 * スレッドを指定する
 */
function detectThread()
{
	global $_conf, $host, $bbs, $key, $ls;
	
	// スレURLの直接指定
	if (($nama_url = $_GET['nama_url']) || ($nama_url = $_GET['url'])) { 
	
			// 2ch or pink - http://choco.2ch.net/test/read.cgi/event/1027770702/
			if (preg_match("/http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?/", $nama_url, $matches)) {
				$host = $matches[1];
				$bbs = $matches[3];
				$key = $matches[4];
				$ls = $matches[6];
				
			// 2ch or pink 過去ログhtml - http://pc.2ch.net/mac/kako/1015/10153/1015358199.html
			} elseif ( preg_match("/(http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html/", $nama_url, $matches) ){ //2ch pink 過去ログhtml
				$host = $matches[2];
				$bbs = $matches[5];
				$key = $matches[7];
				$kakolog_uri = $matches[1];
				$_GET['kakolog'] = urlencode($kakolog_uri);
				
			// まち＆したらばJBBS - http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
			} elseif ( preg_match("/http:\/\/([^\/]+\.machibbs\.com|[^\/]+\.machi\.to)\/bbs\/read\.(pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(&START=([0-9]+))?(&END=([0-9]+))?[^\"]*/", $nama_url, $matches) ){
				$host = $matches[1];
				$bbs = $matches[3];
				$key = $matches[4];
				$ls = $matches[6] ."-". $matches[8];
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
	
	} else {
		if ($_GET['host']) { $host = $_GET['host']; } // "pc.2ch.net"
		if ($_POST['host']) { $host = $_POST['host']; }
		if ($_GET['bbs']) { $bbs = $_GET['bbs']; } // "php"
		if ($_POST['bbs']) { $bbs = $_POST['bbs']; }
		if ($_GET['key']) { $key = $_GET['key']; } // "1022999539"
		if ($_POST['key']) { $key = $_POST['key']; }
		if ($_GET['ls']) {$ls = $_GET['ls']; } // "all"
		if ($_POST['ls']) { $ls = $_POST['ls']; }
	}
	
	if (!($host && $bbs && $key)) {
		$htm['nama_url'] = htmlspecialchars($nama_url);
		$msg = "p2 - {$_conf['read_php']}: スレッドの指定が変です。<br>"
			. "<a href=\"{$htm['nama_url']}\">" .$htm['nama_url']."</a>";
		die($msg);
	}
}

/**
 * 履歴を記録する
 */
function recRecent($data)
{
	global $_conf;
	
	// $_conf['rct_file'] ファイルがなければ生成
	FileCtl::make_datafile($_conf['rct_file'], $_conf['rct_perm']);
	
	$lines = @file($_conf['rct_file']); // 読み込み

	// 最初に重複要素を削除
	if ($lines) {
		foreach ($lines as $line) {
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

	while (sizeof($neolines) > $_conf['rct_rec_num']) {
		array_pop($neolines);
	}
	
	// 書き込む
	$fp = @fopen($_conf['rct_file'], 'wb') or die("Error: {$_conf['rct_file']} を更新できませんでした");
	if ($neolines) {
		@flock($fp, LOCK_EX);
		foreach ($neolines as $l) {
			fputs($fp, $l."\n");
		}
		@flock($fp, LOCK_UN);
	}
	fclose($fp);
}


?>