<?php
// p2 - データコントロール系の関数群

require_once("filectl_class.inc");
require_once './p2util.class.php';	// p2用のユーティリティクラス

/**
 * subject.txtをダウンロードして保存する
 */
function subjectDownload($url, $subjectfile)
{
	global $datdir, $_info_msg_ht, $p2version, $ext_win_target, $fsockopen_time_limit, $proxy;

	if (file_exists($subjectfile)) {
		if ($_GET['norefresh'] or isset($_GET['submit_kensaku']) || isset($_POST['submit_kensaku'])) { // 検索
			return;
		}
		$modified = gmdate("D, d M Y H:i:s", filemtime($subjectfile))." GMT";
	} else {
		$modified = false;
	}

	if (extension_loaded('zlib') and strstr($url, ".2ch.net")){
		$headers="Accept-Encoding: gzip\r\n";
	}

	// したらばのlivedoor移転に対応。読込先をlivedoorとする。
	$url = P2Util::adjustHostJbbs($url);

	//DL
	include_once("./wap.inc");
	$wap_ua = new UserAgent;
	$wap_ua->setAgent("Monazilla/1.00 p2/".$p2version);
	$wap_ua->setTimeout($fsockopen_time_limit);
	$wap_req = new Request;
	$wap_req->setUrl($url);
	$wap_req->setModified($modified);
	$wap_req->setHeaders($headers);
	if($proxy['use']){
		$wap_req->setProxy($proxy['host'], $proxy['port']);
	}
	$wap_res = $wap_ua->request($wap_req);
	
	if ($wap_res->is_error()) {
		$url_t = throughIme($wap_req->url);
		$_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
		$_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$ext_win_target}>{$wap_req->url}</a> に接続できませんでした。</div>";
	}else{
		$body = $wap_res->content;
	}
	
	// ■ DL成功して かつ 更新されていたら
	if ($wap_res->is_success() && $wap_res->code != "304") {
		
		// したらばならEUCをSJISに変換
		if (strstr($subjectfile, $datdir."/jbbs.shitaraba.com") || strstr($subjectfile, $datdir."/jbbs.livedoor.com") || strstr($subjectfile, $datdir."/jbbs.livedoor.jp")) {
			include_once("./strctl_class.inc");
			$body = StrCtl::p2EUCtoSJIS($body);
		}
		
		$fp = @fopen($subjectfile,"wb") or die("Error: {$subjectfile} を更新できませんでした");
		fwrite($fp, $body);
		fclose($fp);
		
	}
	
	return $wap_res;
}

/**
 * ファイルをダウンロードして保存する
 */
function fileDownload($url, $localfile, $disp_error = 1)
{
	global $_conf, $_info_msg_ht, $ext_win_target, $fsockopen_time_limit, $proxy;

	$perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;
	
	if (file_exists($localfile)) {
		$modified = gmdate("D, d M Y H:i:s", filemtime($localfile))." GMT";
	} else {
		$modified = false;
	}

	// DL
	include_once("./wap.inc");
	$wap_ua = new UserAgent;
	$wap_ua->setTimeout($fsockopen_time_limit);
	$wap_req = new Request;
	$wap_req->setUrl($url);
	$wap_req->setModified($modified);
	if ($proxy['use']) {
		$wap_req->setProxy($proxy['host'], $proxy['port']);
	}
	$wap_res = $wap_ua->request($wap_req);
	
	if ($wap_res->is_error() && $disp_error) {
		$url_t = throughIme($wap_req->url);
		$_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
		$_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$ext_win_target}>{$wap_req->url}</a> に接続できませんでした。</div>";
	}
	
	// 更新されていたら
	if ($wap_res->is_success() && $wap_res->code != "304") {
		$fdat = fopen($localfile, "wb") or die("Error: {$localfile} を更新できませんでした");
		fwrite($fdat, $wap_res->content);
		fclose($fdat);
		chmod($localfile, $perm);
	}

	return $wap_res;
}

//===============================================
// ダウンロードURLからキャッシュファイルパスを返す
//===============================================
function cacheForDL($url){
	global $prefdir;
	$URL=parse_url($url); //URL分解
	$cachefile = $prefdir."/p2_cache/{$URL['host']}{$URL['path']}";
	FileCtl::mkdir_for($cachefile);
	return $cachefile;
}

//===============================================
// ホストからクッキーファイルパスを返す関数
//===============================================
function cacheForCookie($host){
	global $prefdir;
	$cachefile = $prefdir."/p2_cookie/{$host}/p2_cookie.txt";
	FileCtl::mkdir_for($cachefile);
	return $cachefile;
}

//===============================================
// hostとbbsから板名を返す関数
//===============================================
function getItaName($host, $bbs){
	global $prefdir, $ita_names, $p2_perm;
	
	if(! isset($ita_names["$host/$bbs"]) ){
		$datdir_host = datdirOfHost($host);
		$p2_setting_txt = $datdir_host."/".$bbs."/p2_setting.txt";
		$p2_setting_cont = FileCtl::get_file_contents($p2_setting_txt);
		if($p2_setting_cont){$p2_setting = unserialize($p2_setting_cont);}
		$ita_names["$host/$bbs"] = $p2_setting['itaj'];
	}

	/* 板名Longの取得
	if(! $p2_setting['itaj']){ //itaj未セットで2ch pink ならSETTING.TXTを読んでセット
		if( P2Util::isHost2chs($host) ){
			$tempfile=$prefdir."/SETTING.TXT.temp";
			fileDownload("http://{$host}/{$bbs}/SETTING.TXT", $tempfile);
			//$setting=getHttpContents("http://{$host}/{$bbs}/SETTING.TXT", "", "GET", "", array(""), $httpua="p2");
			$setting=file($tempfile);
			if(file_exists($tempfile)){ unlink($tempfile); }
			if($setting){
				foreach($setting as $sl){
					$sl=trim($sl);
					if(preg_match("/^BBS_TITLE=(.+)/", $sl, $matches)){
						$p2_setting['itaj']=$matches[1];
					}
				}
				if($p2_setting['itaj']){
					FileCtl::make_datafile($p2_setting_txt, $p2_perm);
					if($p2_setting){$p2_setting_cont = serialize($p2_setting);}
					if($p2_setting_cont){
						$fp = fopen($p2_setting_txt, "w") or die("Error: $p2_setting_txt を更新できませんでした");
						fputs($fp, $p2_setting_cont);
						fclose($fp);
					}
				}
			}
		}
	}
	*/
	
	return $ita_names["$host/$bbs"];
}

//===============================================
// hostからdatの保存ディレクトリを返す関数
//===============================================
function datdirOfHost($host){
	global $datdir;
	if( P2Util::isHost2chs($host) ){ // 2channel or bbspink
		$datdir_host = $datdir."/2channel";
	}elseif( P2Util::isHostMachiBbs($host) ){ //machibbs.com
		$datdir_host = $datdir."/machibbs.com";
	}else{
		$datdir_host = $datdir."/".$host;
	}
	return $datdir_host;
}

//===============================================
// key.idx に data を記録する関数
//===============================================
function setKeyIdx($keyidx, $data){
	global $key_perm;
	
	FileCtl::make_datafile($keyidx, $key_perm);
	$fp=fopen($keyidx, "w") or die("Error: {$keyidx} を更新できませんでした");
	fputs($fp, $data);
	fclose($fp);
}

//===============================================
// ゲートを通すためのURL変換関数
//===============================================
function throughIme($url){
	global $through_ime, $p2ime_url;
	
	if($through_ime=="2ch"){
		$purl = parse_url($url);
		$url_r = $purl[scheme] . "://ime.nu/" . $purl[host] . $purl[path];
	}elseif($through_ime=="p2"){
		$url_r = $p2ime_url . "?url=" . $url;
	}elseif($through_ime=="p2m"){
		$url_r = $p2ime_url . "?m=1&amp;url=" . $url;
	}else{
		$url_r = $url;
	}
	return $url_r;
}


//===============================================
// リストのナビ範囲を返す関数
//===============================================
function getListNaviRange($disp_from, $disp_range, $disp_all_num){

	$disp_end=0;
	$disp_navi=array();

	if(!$disp_all_num){
		$disp_navi['from']=0;
		$disp_navi['end']=0;
		$disp_navi['all_once']=true;
		$disp_navi['mae_from']=1;
		$disp_navi['tugi_from']=1;
		return $disp_navi;
	}	


	$disp_navi['from']=$disp_from;
	
	$disp_range = $disp_range-1;
	
	if($disp_navi['from'] > $disp_all_num){ //fromが越えた
		$disp_navi['from'] = $disp_all_num - $disp_range;
		if($disp_navi['from'] < 1){
			$disp_navi['from'] = 1;
		}
		$disp_navi['end'] = $disp_all_num;
					
	}else{//from 越えない
		if($disp_navi['from'] + $disp_range > $disp_all_num){ //end 越えた
			$disp_navi['end'] = $disp_all_num;
			if($disp_navi['from'] == 1){
				$disp_navi['all_once'] = true;
			}
		}else{// end 越えない
			$disp_navi['end'] = $disp_from + $disp_range;
		}
	}
		
	$disp_navi['mae_from'] = $disp_from -1 -$disp_range;
	if($disp_navi['mae_from'] < 1){
		$disp_navi['mae_from'] = 1;
	}	
	$disp_navi['tugi_from'] = $disp_navi['end'] +1;


	if($disp_navi['from']==$disp_navi['end']){
		$range_on_st = $disp_navi['from'];
	}else{
		$range_on_st = "{$disp_navi['from']}-{$disp_navi['end']}";
	}
	$disp_navi['range_st']="{$range_on_st}/{$disp_all_num} ";


	return $disp_navi;
	
}

//パーミッション注意喚起================
function datadir_writable_check($aDir)
{
	global $_info_msg_ht, $data_dir_perm;
	if( ! is_dir($aDir) ){
		$_info_msg_ht .= <<<EOP
<p class="infomsg">注意: データ保存用ディレクトリがありません。<br>
{$aDir}<br>
EOP;
		if( is_dir( dirname( realpath($aDir) ) ) && is_writable( dirname( realpath($aDir) ) )){
			$_info_msg_ht .="ディレクトリの自動作成を試みます...<br>";
			if (mkdir($aDir, $data_dir_perm)) {
				$_info_msg_ht .="ディレクトリの自動作成が成功しました。</p>";
				chmod($aDir, $data_dir_perm);
			} else {
				$_info_msg_ht .= "ディレクトリを自動作成できませんでした。<br>手動でディレクトリを作成し、パーミッションを設定して下さい。</p>";
			}
		}else{
				$_info_msg_ht .= "ディレクトリを作成し、パーミッションを設定して下さい。</p>";
		}
	}elseif( ! is_writable($aDir) ){
		$_info_msg_ht .= <<<EOP
<p class="infomsg">注意: データ保存用ディレクトリに書き込み権限がありません。<br>
{$aDir}<br>
ディレクトリのパーミッションを見直して下さい。</p>
EOP;
	}
}

?>