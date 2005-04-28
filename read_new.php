<?php
/*
	p2 - スレッド表示スクリプト - 新着まとめ読み
	フレーム分割画面、右下部分
*/

include_once './conf/conf.inc.php'; // 設定
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './threadlist.class.php'; // スレッドリスト クラス
require_once './thread.class.php'; // スレッド クラス
require_once './threadread.class.php'; // スレッドリード クラス
require_once './ngabornctl.class.php';

require_once './read_new.inc.php';

authorize(); // ユーザ認証

// まとめよみのキャッシュ読み
if (!empty($_GET['cview'])) {
	$cnum = (isset($_GET['cnum'])) ? intval($_GET['cnum']) : NULL;
	if ($cont = getMatomeCache($cnum)) {
		echo $cont;
	} else {
		echo 'p2 error: 新着まとめ読みのキャッシュがないよ';
	}
	exit;
}

//==================================================================
// ■変数
//==================================================================
if (isset($_conf['rnum_all_range']) and $_conf['rnum_all_range'] > 0) {
	$GLOBALS['rnum_all_range'] = $_conf['rnum_all_range'];
}

$sb_view = "shinchaku";
$newtime = date("gis");

$sid_q = (defined('SID')) ? '&amp;'.strip_tags(SID) : '';

//=================================================
// 板の指定
//=================================================
if (isset($_GET['host'])) { $host = $_GET['host']; }
if (isset($_POST['host'])) { $host = $_POST['host']; }
if (isset($_GET['bbs'])) { $bbs = $_GET['bbs']; }
if (isset($_POST['bbs'])) { $bbs = $_POST['bbs']; }
if (isset($_GET['spmode'])) { $spmode = $_GET['spmode']; }
if (isset($_POST['spmode'])) { $spmode = $_POST['spmode']; }

if ((!isset($host) || !isset($bbs)) && !isset($spmode)) {
	die('p2 error: 必要な引数が指定されていません');
}

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//====================================================================
// ■メイン
//====================================================================

register_shutdown_function('saveMatomeCache');

$read_new_html = '';
ob_start();

$aThreadList =& new ThreadList();

// ■板とモードのセット===================================
if ($spmode) {
	if ($spmode == "taborn" or $spmode == "soko") {
		$aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
	}
	$aThreadList->setSpMode($spmode);
	
} else {
	$aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));

	// ■スレッドあぼーんリスト読込
	$datdir_host = P2Util::datdirOfHost($host);
	$tabornlines = @file($datdir_host."/".$bbs."/p2_threads_aborn.idx");
	if ($tabornlines) {
		$ta_num = sizeOf($tabornlines);
		foreach ($tabornlines as $l) {
			$tarray = explode('<>', rtrim($l));
			$ta_keys[ $tarray[1] ] = true;
		}
	}
}

// ■ソースリスト読込 ==================================
$lines = $aThreadList->readList();

// ■ページヘッダ表示 ===================================
$ptitle_hd = htmlspecialchars($aThreadList->ptitle);
$ptitle_ht = "{$ptitle_hd} の 新着まとめ読み";

if ($aThreadList->spmode) {
	$sb_ht = <<<EOP
		<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}" target="subject">{$ptitle_hd}</a>
EOP;
} else {
	$sb_ht = <<<EOP
		<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}" target="subject">{$ptitle_hd}</a>
EOP;
}

//include($read_header_inc);

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOHEADER
<html lang="ja">
<head>
	{$_conf['meta_charset_ht']}
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle_ht}</title>
EOHEADER;

@include("style/style_css.inc"); //スタイルシート
@include("style/read_css.inc"); //スタイルシート

echo <<<EOHEADER
	<script type="text/javascript" src="js/basic.js"></script>
	<script type="text/javascript" src="js/respopup.js"></script>
	<script type="text/javascript" src="js/htmlpopup.js"></script>\n
EOHEADER;

echo <<<EOHEADER
	<script type="text/javascript">
	<!--
	gIsPageLoaded = false;
	// お気にセット関数
	function setFav(host, bbs, key, favdo, obj)
	{
		/*
		// ページの読み込みが完了していなければ、なにもしない
		if (!gIsPageLoaded) {
			return false;
		}
		*/
		
		var objHTTP = getXmlHttp();
		if (!objHTTP) {
			// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
			// XMLHTTP（と obj.parentNode.innerHTML） に未対応なら小窓で
			return OpenSubWin('info.php?host='+host+'&amp;bbs='+bbs+'&amp;key='+key+'&amp;setfav='+favdo+'&amp;popup=2',{$STYLE['info_pop_size']},0,0);
		}
		// キャッシュ回避用
		var now = new Date();
		// 引数の文字列は encodeURIComponent でエスケープするのがよい
		query = 'host='+host+'&bbs='+bbs+'&key='+key+'&setfav='+favdo+'&nc='+now.getTime();
		url = 'httpcmd.php?' + query + '&cmd=setfav';	// スクリプトと、コマンド指定
		objHTTP.open('GET', url, false);
		objHTTP.send(null);
		if (objHTTP.status != 200 || objHTTP.readyState != 4 && !objHTTP.responseText) {
			// alert("Error: XMLHTTP 結果の受信に失敗しました") ;
		}
		var res = objHTTP.responseText;
		var rmsg = "";
		if (res) {
			if (res == '1') {
				rmsg = '完了';
			}
			if (rmsg) {
				if (favdo == '1') {
					nextset = '0';
					favmark = '★';
					favtitle = 'お気にスレから外す';
				} else {
					nextset = '1';
					favmark = '+';
					favtitle = 'お気にスレに追加';
				}
				var favhtm = '<a href="info.php?host='+host+'&amp;bbs='+bbs+'&amp;key='+key+'&amp;setfav='+nextset+'" target="info" onClick="return setFav(\''+host+'\', \''+bbs+'\', \''+key+'\', \''+nextset+'\', this);" title="'+favtitle+'">お気に'+favmark+'</a>';
				obj.parentNode.innerHTML = favhtm;
			}
		}
		return false;
	}

	// ログ削除関数
	function deleLog(query, obj)
	{
		/*
		// ページの読み込み完了していなければリンクで
		if (!gIsPageLoaded) {
			return true;
		}
		*/

		var objHTTP = getXmlHttp();
		
		if (!objHTTP) {
			// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
			
			// XMLHTTP（と obj.parentNode.innerHTML） に未対応なら通常リンクで // [better]小窓の方がベター
			return true;
		}

		// キャッシュ回避用
		var now = new Date();
		// 引数の文字列は encodeURIComponent でエスケープするのがよい
		query = query + '&nc='+now.getTime();
		url = 'httpcmd.php?' + query + '&cmd=delelog';	// スクリプトと、コマンド指定
		objHTTP.open('GET', url, false);
		objHTTP.send(null);
		if (objHTTP.status != 200 || objHTTP.readyState != 4 && !objHTTP.responseText) {
			// alert("Error: XMLHTTP 結果の受信に失敗しました") ;
		}
		var res = objHTTP.responseText;
		var rmsg = "";
		
		if (res) {
			// alert(res);
			if (res == '1') {
				rmsg = '完了';
			} else if (res == '2') {
				rmsg = 'なし';
			}
			if (rmsg) {
				obj.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.style.filter = 'Gray()';	// IE ActiveX用
				obj.parentNode.innerHTML = rmsg;
			}
		}

		return false;
	}
	
	function pageLoaded()
	{
		gIsPageLoaded = true;
		setWinTitle();
	}
	-->
	</script>\n
EOHEADER;

echo <<<EOP
</head>
<body onLoad="pageLoaded();">
<div id="popUpContainer"></div>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = "";

//echo $ptitle_ht."<br>";

//==============================================================
// ■それぞれの行解析
//==============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize ; $x++) {
	
	if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
		break;
	}
	
	$l = $lines[$x];
	$aThread =& new ThreadRead();
	
	$aThread->torder = $x + 1;

	// ■データ読み込み
	// spmodeなら
	if ($aThreadList->spmode) {
		switch ($aThreadList->spmode) {
	    case "recent": // 履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
	    case "res_hist": // 書き込み履歴
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
	    case "fav": // お気に
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
		case "taborn":	// スレッドあぼーん
	        $aThread->getThreadInfoFromExtIdxLine($l);
			$aThread->host = $aThreadList->host;
			$aThread->bbs = $aThreadList->bbs;
	        break;
		case "palace":	// スレの殿堂
	        $aThread->getThreadInfoFromExtIdxLine($l);
	        break;
		}
	// subject (not spmode)の場合
	} else {
		$aThread->getThreadInfoFromSubjectTxtLine($l);
		$aThread->host = $aThreadList->host;
		$aThread->bbs = $aThreadList->bbs;
	}
	
	// hostもbbsも不明ならスキップ
	if (!($aThread->host && $aThread->bbs)) {
		unset($aThread);
		continue;
	}
	
	
	$aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
	
	// 既得スレッドデータをidxから取得
	$aThread->getThreadInfoFromIdx();
		
	// ■新着のみ(for subject) =========================================
	if (!$aThreadList->spmode and $sb_view == "shinchaku" and !$_GET['word']) { 
		if ($aThread->unum < 1) {
			unset($aThread);
			continue;
		}
	}

	// ■スレッドあぼーんチェック =====================================
	if ($aThreadList->spmode != "taborn" and $ta_keys[$aThread->key]) { 
			unset($ta_keys[$aThread->key]);
			continue; // あぼーんスレはスキップ
	}

	// ■ spmode(殿堂入りを除く)なら ====================================
	if ($aThreadList->spmode && $sb_view != "edit") { 
		
		// subject.txt が未DLなら落としてデータを配列に格納
		if (!$subject_txts["$aThread->host/$aThread->bbs"]) {
			$datdir_host = P2Util::datdirOfHost($aThread->host);
			$subject_url = "http://{$aThread->host}/{$aThread->bbs}/subject.txt";
			
			$subjectfile = "{$datdir_host}/{$aThread->bbs}/subject.txt";
			
			FileCtl::mkdir_for($subjectfile); // 板ディレクトリが無ければ作る
			if (!($word_fm and file_exists($subjectfile))) {
				P2Util::subjectDownload($subject_url, $subjectfile);
			}
			if (extension_loaded('zlib') and strstr($aThread->host, ".2ch.net")) {
				$subject_txts["$aThread->host/$aThread->bbs"] = @gzfile($subjectfile);
			} else {
				$subject_txts["$aThread->host/$aThread->bbs"] = @file($subjectfile);
			}
			
		}
		
		// ■スレ情報取得 =============================
		if ($subject_txts["$aThread->host/$aThread->bbs"]) {
			foreach ($subject_txts["$aThread->host/$aThread->bbs"] as $l) {
				if (@preg_match("/^{$aThread->key}/", $l)) {
					$aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
					break;
				}
			}
		}
		
		// 新着のみ(for spmode) ===============================
		if ($sb_view == "shinchaku" and !$_GET['word']) { 
			if ($aThread->unum < 1) {
				unset($aThread);
				continue;
			}
		}
	}
	
	if ($aThread->isonline) { $online_num++; }	// 生存数set
	
	echo $_info_msg_ht;
	$_info_msg_ht = "";
	
	$read_new_html .= ob_get_contents();
	@ob_end_flush();
	ob_start();
	
	if (($aThread->readnum < 1) || $aThread->unum) {
		readNew($aThread);
	} elseif ($aThread->diedat) {
		echo $aThread->getdat_error_msg_ht;
		echo "<hr>\n";
	}
	
	
	// リストに追加 ========================================
	// $aThreadList->addThread($aThread);
	$aThreadList->num++;
	unset($aThread);
}

// $aThread =& new ThreadRead();

//======================================================================
// ■ スレッドの新着部分を読み込んで表示する
//======================================================================
function readNew(&$aThread)
{
	global $_conf, $newthre_num, $STYLE;
	global $_info_msg_ht;

	$newthre_num++;
	
	//==========================================================
	// ■ idxの読み込み
	//==========================================================
	
	// hostを分解してidxファイルのパスを求める
	$aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
	
	// FileCtl::mkdir_for($aThread->keyidx);	 // 板ディレクトリが無ければ作る // この操作はおそらく不要

	$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
	if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

	// idxファイルがあれば読み込む
	if (is_readable($aThread->keyidx)) {
		$lines = @file($aThread->keyidx);
		$data = explode('<>', rtrim($lines[0]));
	}
	$aThread->getThreadInfoFromIdx();
	
	//==================================================================
	// ■DATのダウンロード
	//==================================================================
	if (!($word and file_exists($aThread->keydat))) {
		$aThread->downloadDat();
	}
	
	// DATを読み込み
	$aThread->readDat();
	$aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定
	
	//===========================================================
	// ■表示レス番の範囲を設定
	//===========================================================
	if ($aThread->isKitoku()) { // 取得済みなら
		$from_num = $aThread->readnum +1 - $_conf['respointer'] - $_conf['before_respointer_new'];
		if ($from_num < 1) {
			$from_num = 1;
		} elseif ($from_num > $aThread->rescount) {
			$from_num = $aThread->rescount - $_conf['respointer'] - $_conf['before_respointer_new'];
		}

		//if(! $ls){
			$ls = "$from_num-";
		//}
	}
	
	$aThread->lsToPoint($ls);
	
	//==================================================================
	// ■ヘッダ 表示
	//==================================================================
	$motothre_url = $aThread->getMotoThread($GLOBALS['ls']);
	
	$ttitle_en = base64_encode($aThread->ttitle);
	$ttitle_urlen = rawurlencode($ttitle_en);
	$ttitle_en_q ="&amp;ttitle_en=".$ttitle_urlen;
	$bbs_q = "&amp;bbs=".$aThread->bbs;
	$key_q = "&amp;key=".$aThread->key;
	$popup_q = "&amp;popup=1";
	
	//include($read_header_inc);
	
	$prev_thre_num = $newthre_num - 1;
	$next_thre_num = $newthre_num + 1;
	if ($prev_thre_num != 0) {
		$prev_thre_ht = "<a href=\"#ntt{$prev_thre_num}\">▲</a>";
	}
	$next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a>	";
	
	echo $_info_msg_ht;
	$_info_msg_ht = "";
	
	// ■ヘッダ部分HTML	
	$read_header_ht = <<<EOP
	<table id="ntt{$newthre_num}" width="100%" style="padding:0px 10px 0px 0px;">
		<tr>
			<td align="left">
				<h3 class="thread_title">{$aThread->ttitle_hd}</h3>
			</td>
			<td align="right">
				{$prev_thre_ht}
				{$next_thre_ht}
			</td>
		</tr>
	</table>\n
EOP;
	
	//==================================================================
	// ■ローカルDatを読み込んでHTML表示
	//==================================================================
	$aThread->resrange['nofirst'] = true;
	$GLOBALS['newres_to_show_flag'] = false;
	if ($aThread->rescount) {
		// $aThread->datToHtml(); //dat を html に変換表示
		include_once './showthread.class.php'; // HTML表示クラス
		include_once './showthreadpc.class.php'; // HTML表示クラス
		$aShowThread =& new ShowThreadPc($aThread);

		$res1 = $aShowThread->quoteOne();
		$read_cont_ht = $res1['q'];
		
		$read_cont_ht .= $aShowThread->getDatToHtml();

		unset($aShowThread);
	}
	
	//==================================================================
	// ■フッタ 表示
	//==================================================================
	//include($read_footer_inc);
	
	//----------------------------------------------
	// $read_footer_navi_new  続きを読む 新着レスの表示
	$newtime = date("gis");  // リンクをクリックしても再読込しない仕様に対抗するダミークエリー
	
	$info_st = "情報";
	$delete_st = "削除";
	$prev_st = "前";
	$next_st = "次";

	$read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-&amp;nt=$newtime#r{$aThread->rescount}\">新着レスの表示</a>";
	
	$dores_ht = <<<EOP
		<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}" target='_self' onClick="return OpenSubWin('post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}{$popup_q}&amp;from_read_new=1',{$STYLE['post_pop_size']},0,0)">レス</a>
EOP;

	// ■ツールバー部分HTML =======
	
	// お気にマーク設定
	$favmark = (!empty($aThread->fav)) ? '★' : '+';
	$favdo = (!empty($aThread->fav)) ? 0 : 1;
	$favtitle = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
	$favdo_q = '&amp;setfav='.$favdo;
	$itaj_hd = htmlspecialchars($aThread->itaj);
	
	$toolbar_right_ht = <<<EOTOOLBAR
			<a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}" target="subject" title="板を開く">{$itaj_hd}</a>
			<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}" target="info" onClick="return OpenSubWin('info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$popup_q}',{$STYLE['info_pop_size']},0,0)" title="スレッド情報を表示">{$info_st}</a> 
			<span class="favdo"><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$favdo_q}{$sid_q}" target="info" onClick="return setFav('{$aThread->host}', '{$aThread->bbs}', '{$aThread->key}', '{$favdo}', this);" title="{$favtitle}">お気に{$favmark}</a></span> 
			<span><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=true" target="info" onClick="return deleLog('host={$aThread->host}{$bbs_q}{$key_q}', this);" title="ログを削除する">{$delete_st}</a></span> 
<!--			<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;taborn=2" target="info" onClick="return OpenSubWin('info.php?host={$aThread->host}{$bbs_q}&amp;key={$aThread->key}{$ttitle_en_q}&amp;popup=2&amp;taborn=2',{$STYLE['info_pop_size']},0,0)" title="スレッドのあぼーん状態をトグルする">あぼん</a> -->
			<a href="{$motothre_url}" title="板サーバ上のオリジナルスレを表示">元スレ</a>
EOTOOLBAR;

	// レスのすばやさ
	$spd_ht = "";
	if ($spd_st = $aThread->getTimePerRes() and $spd_st != "-") {
		$spd_ht = '<span class="spd" title="すばやさ＝時間/レス">'."" . $spd_st."".'</span>';
	}

	// ■フッタ部分HTML
	$read_footer_ht = <<<EOP
		<table width="100%" style="padding:0px 10px 0px 0px;">
			<tr>
				<td align="left">
					{$res1['body']} | <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;offline=1&amp;rc={$aThread->rescount}#r{$aThread->rescount}">{$aThread->ttitle_hd}</a> | {$dores_ht} {$spd_ht}
				</td>
				<td align="right">
					{$toolbar_right_ht}
				</td>
				<td align="right">
					<a href="#ntt{$newthre_num}">▲</a>
				</td>
			</tr>
		</table>\n
EOP;

	// 透明あぼーんで表示がない場合はスキップ
	if ($GLOBALS['newres_to_show_flag']) {
		echo '<div style="width:100%;">'."\n";	// ほぼIE ActiveXのGray()のためだけに囲ってある
		echo $read_header_ht;
		echo $read_cont_ht;
		echo $read_footer_ht;
		echo '</div>'."\n\n";
		echo '<hr>'."\n\n";
	}

	flush();
	
	//==================================================================
	// ■key.idxの値設定
	//==================================================================
	if ($aThread->rescount) {
	
		$aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));
		
		$newline = $aThread->readnum + 1;	// $newlineは廃止予定だが、旧互換用に念のため
		
		$s = "{$aThread->ttitle}<>{$aThread->key}<>$data[2]<>{$aThread->rescount}<>{$aThread->modified}<>{$aThread->readnum}<>$data[6]<>$data[7]<>$data[8]<>{$newline}";
		P2Util::recKeyIdx($aThread->keyidx, $s); // key.idxに記録
	}
	
	unset($aThread);
}

//==================================================================
// ■ページフッタ表示
//==================================================================
$newthre_num++;

if (!$aThreadList->num) {
	$GLOBALS['matome_naipo'] = TRUE;
	echo "新着レスはないぽ";
	echo "<hr>";
}

if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0) {
	echo <<<EOP
	<div id="ntt{$_newthre_num}" align="center">
		{$sb_ht} の <a href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}">新着まとめ読みを更新</a>
	</div>\n
EOP;
} else {
	 echo <<<EOP
	<div id="ntt{$_newthre_num}" align="center">
		{$sb_ht} の <a href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}&amp;norefresh=1">新着まとめ読みの続き</a>
	</div>\n
EOP;
}

echo '</body></html>';

$read_new_html .= ob_get_contents();
@ob_end_flush();

// ■NGあぼーんを記録
NgAbornCtl::saveNgAborns();
?>
