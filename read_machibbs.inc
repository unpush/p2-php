<?php
/* p2 - まちBBS用の関数（兼 JBBS@したらば）*/

require_once './filectl.class.php';
require_once './p2util.class.php';	// p2用のユーティリティクラス

/**
 * まちBBSのread.pl を読んで datに保存する
 */
function machiDownload()
{
	global $aThread, $machi_latest_num;

	$machi_latest_num = "";
	
	//既得datの取得レス数が適性かどうかを念のためチェック
	if (file_exists($aThread->keydat)) {
		$dls = @file($aThread->keydat);
		if (sizeof($dls) != $aThread->rnum) {
			//echo "bad size!<br>";
			unlink($aThread->keydat);
			$aThread->rnum = 0;
		}
	}
	
	$aThread->rnum = intval($aThread->rnum);
	if ($aThread->rnum == 0) {
		$mode = 'wb';
		$zero_read = true;
	} else {
		$mode = 'ab';
	}

	$START = $zero_read ? 1 : $aThread->rnum + 1;

	// JBBS@したらば
	if (P2Util::isHostJbbsShitaraba($aThread->host)) {
		// したらばのlivedoor移転に対応。読込先をlivedoorとする。
		$host = P2Util::adjustHostJbbs($aThread->host);
		list($host, $category, ) = explode('/', $host);
		$machiurl = "http://{$host}/bbs/read.cgi/{$category}/{$aThread->bbs}/{$aThread->key}/{$START}-";
		
	// まちBBS
	} else { 
		$machiurl = "http://{$aThread->host}/bbs/read.pl?BBS={$aThread->bbs}&KEY={$aThread->key}&START={$START}";
	}

	$tempfile=$aThread->keydat.".html.temp";
	FileCtl::mkdir_for($tempfile);
	
	$machiurl_res = P2Util::fileDownload($machiurl, $tempfile);
	
	if( $machiurl_res->is_error() ){
		$aThread->diedat=true;
		return false;
	}
	
	// したらばならEUCをSJISに変換
	if (P2Util::isHostJbbsShitaraba($aThread->host)) { 
	
		$temp_data = FileCtl::get_file_contents($tempfile);
		
		include_once './strctl.class.php';
		$temp_data = StrCtl::p2EUCtoSJIS($temp_data);
		$fp = @fopen($tempfile, 'wb') or die("Error: $tempfile を更新できませんでした");
		fputs($fp, $temp_data);
		fclose($fp);
	}
	
	$mlines = @file($tempfile);
	if (file_exists($tempfile)) { unlink($tempfile); }

	// （まちBBS）<html>error</html>
	if (trim($mlines[0]) == "<html>error</html>") {
		$aThread->getdat_error_msg_ht .= "error";
		$aThread->diedat = true;
		return false;
	// （JBBS）ERROR!: スレッドがありません。過去ログ倉庫にもありません。
	} elseif (preg_match("/^ERROR.*$/i", $mlines[0], $matches)) {
		$aThread->getdat_error_msg_ht .= $matches[0];
		$aThread->diedat = true;
		return false;
	}
	
	$mdatlines = machiHtmltoDatLines($mlines);

	//書き込む=====================================
	$fp = @fopen($aThread->keydat, $mode) or die("Error: $aThread->keydat を更新できませんでした");
	for($i=$START; $i<=$machi_latest_num; $i++){
		if($mdatlines[$i]){
			fputs($fp, $mdatlines[$i]);
		}else{
			fputs($fp, "あぼーん<>あぼーん<>あぼーん<>あぼーん<>\n");
		}
	}
	fclose($fp);
	
	$aThread->isonline = true;
	return true;
}


/**
 * まちBBSのread.plで読み込んだHTMLをdatに変換する
 */
function machiHtmltoDatLines($mlines)
{
	global $machi_latest_num;

	if (!$mlines) {return false;}
	$mdatlines = "";
	
	foreach ($mlines as $ml) {
		$ml = rtrim($ml);
		if (!$tuduku) {unset($order,$mail ,$name ,$date, $ip ,$body);}

		if ($tuduku) {
			if (preg_match("/^ \]<\/font><br><dd>(.*) <br><br>$/i", $ml, $matches)) {
				$body=$matches[1];
			} else {
				unset($tuduku);
				continue;
			}
		} elseif (preg_match("/^<dt>(?:<a[^>]+?>)?(\d+)(?:<\/a>)? 名前：(<font color=\"#.+?\">|<a href=\"mailto:(.*)\">)<b> (.+) <\/b>(<\/font>|<\/a>) 投稿日： (.+)<br><dd>(.*) <br><br>$/i", $ml, $matches)) {
			$order = $matches[1];
			$mail = $matches[3];
			$name = preg_replace("/<font color=\"?#.+?\"?>(.+)<\/font>/i", "\\1", $matches[4]);
			$date = $matches[6];
			$body = $matches[7];
		} elseif (preg_match("/<title>(.*)<\/title>/i", $ml, $matches)){
			$mtitle = $matches[1];
			continue;
		} elseif (preg_match("/^<dt>(?:<a[^>]+?>)?(\d+)(?:<\/a>)? 名前：(<font color=\"#.+?\">|<a href=\"mailto:(.*)\">)<b> (.+) <\/b>(<\/font>|<\/a>) 投稿日： (.+) <font size=1>\[ (.+)$/i", $ml, $matches)) {
			$order = $matches[1];
			$mail = $matches[3];
			$name = preg_replace("/<font color=\"?#.+?\"?>(.+)<\/font>/i", "\\1", $matches[4]);
			$date = $matches[6];
			$ip = $matches[7];
			$tuduku = true;
			continue;
		}
		
		if ($ip) {
			$date = "$date [$ip]";
		}

		// したらばJBBS jbbs.livedoor.com のlink.cgiを除去
		// <a href="http://jbbs.livedoor.jp/bbs/link.cgi?url=http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif" target="_blank">http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif</a>
		$body = preg_replace('{<a href="(?:http://jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp)))?/bbs/link\.cgi\?url=([^"]+)" target="_blank">([^><]+)</a>}i', '$1', $body);
		
		// リンク外し
		$body = preg_replace('{<a href="(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)</a>}i', '$1', $body);
		
		if ($order == 1) {
			$datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'.$mtitle."\n";
		} else {
			$datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'."\n";
		}
		$mdatlines[$order]=$datline;
		if ($order > $machi_latest_num) {
			$machi_latest_num = $order;
		}
		unset($tuduku);
	}
	
	return $mdatlines;
}

?>