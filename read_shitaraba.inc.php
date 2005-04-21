<?php
/*
	p2 - したらばJBBS jbbs.livedoor.jp 用の関数
    
    // 各種BBSに対応できるプロファイルクラスみたいなのを作りたいものだ。。 aki
*/

require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './filectl.class.php';

/**
 * したらばJBBSの rawmode.cgi を読んで、datに保存する（2ch風に整形）
 */
function shitarabaDownload()
{
	global $aThread;

	$GLOBALS['machi_latest_num'] = '';

	// {{{ 既得datの取得レス数が適性かどうかを念のためチェック
	if (file_exists($aThread->keydat)) {
		$dls = @file($aThread->keydat);
		if (sizeof($dls) != $aThread->gotnum) {
			// echo 'bad size!<br>';
			unlink($aThread->keydat);
			$aThread->gotnum = 0;
		}
	}
    // }}}
	
	if ($aThread->gotnum == 0) {
		$mode = 'wb';
		$START = 1;
	} else {
		$mode = 'ab';
		$START = $aThread->gotnum + 1;
	}

	// JBBS@したらば
	if (P2Util::isHostJbbsShitaraba($aThread->host)) {
		// したらばのlivedoor移転に対応。読込先をlivedoorとする。
		$host = P2Util::adjustHostJbbs($aThread->host);
		list($host, $category, ) = explode('/', $host);
		$machiurl = "http://{$host}/bbs/rawmode.cgi/{$category}/{$aThread->bbs}/{$aThread->key}/{$START}-";
	}

	$tempfile = $aThread->keydat.'.dat.temp';
	
	FileCtl::mkdir_for($tempfile);
	$machiurl_res = P2Util::fileDownload($machiurl, $tempfile);
	
	if ($machiurl_res->is_error()) {
		$aThread->diedat = true;
		return false;
	}
	
	// {{{ したらばならEUCをSJISに変換
	if (P2Util::isHostJbbsShitaraba($aThread->host)) {
		$temp_data = @file_get_contents($tempfile);
		$temp_data = mb_convert_encoding($temp_data, 'SJIS-win', 'EUC-JP');
		FileCtl::file_write_contents($tempfile, $temp_data) or die("Error: {$tempfile} を更新できませんでした");
	}
    // }}}
	
	$mlines = @file($tempfile);
    
    // 一時ファイルを削除する
	if (file_exists($tempfile)) {
		unlink($tempfile);
	}

    // ↓rawmode.cgiではこれは出ないだろう
    /*
	// （JBBS）ERROR!: スレッドがありません。過去ログ倉庫にもありません。
	if (preg_match("/^ERROR.*$/i", $mlines[0], $matches)) {
		$aThread->getdat_error_msg_ht .= $matches[0];
		$aThread->diedat = true;
		return false;
	}
	*/
    
    // {{{ DATを書き込む
	if ($mdatlines =& shitarabaDatTo2chDatLines($mlines)) {
		
		$fp = @fopen($aThread->keydat, $mode) or die("Error: {$aThread->keydat} を更新できませんでした");
		@flock($fp, LOCK_EX);
		for ($i = $START; $i <= $GLOBALS['machi_latest_num']; $i++) {
			if ($mdatlines[$i]) {
				fputs($fp, $mdatlines[$i]);
			} else {
				fputs($fp, "あぼーん<>あぼーん<>あぼーん<>あぼーん<>\n");
			}
		}
		@flock($fp, LOCK_UN);
		fclose($fp);
	}
	// }}}
    
	$aThread->isonline = true;
	
	return true;
}


/**
 * したらばBBSの rawmode.cgi で読み込んだDATを2ch風datに変換する
 *
 * @see shitarabaDownload()
 */
function &shitarabaDatTo2chDatLines(&$mlines)
{
	if (!$mlines) {
		return false;
	}
	$mdatlines = "";
	
	foreach ($mlines as $ml) {
		$ml = rtrim($ml);

        // 1<><font color=#FF0000>管理人</font><>sage<>2005/04/06(水) 21:44:54<>Pandemonium総合スレッドです。次スレは　<a href="/bbs/read.cgi/game/10109/1112791494/950" target="_blank">&gt;&gt;950</a> が誠意を持って申請する事。<br><br>■5W1Hの法則を無視したものは全て放置でお願いします。<br>■粘着・理由亡き晒し・煽り・騙り・ＡＡは放置で。無視できない人は同類とみなされます。<br>■職人に対する粘着行為・各ジョブの叩きなど専門のスレでお願いします。<br>■売名行為の糞コテは完全放置で。レスという餌を与えないようにしましょう。<br>■以上を踏まえて悪戯の度が過ぎる場合は削除依頼スレにお願いします。<br><br>[前スレ]【春房がポップ】Pandemonium(20)Part.41【それすら小物】<br>http://jbbs.livedoor.jp/bbs/read.cgi/game/10109/1109905935/<>【内藤は】Pandemonium(20)Part.42【面の皮も鋼】<>EM04DJXI

        $data = explode('<>', $ml);
        
		$order = $data[0];
		$name = $data[1];
		$mail = $data[2];
		$date = $data[3];
		$body = $data[4];
        if ($order == 1) {
            $mtitle = $data[5];
        }
		if ($data[6]) {
			$date .= " ID:".$data[6];
		}

        /* rawmode.cgi ではこれはない
		// したらばJBBS jbbs.livedoor.com のlink.cgiを除去
		// <a href="http://jbbs.livedoor.jp/bbs/link.cgi?url=http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif" target="_blank">http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif</a>
		$body = preg_replace('{<a href="(?:http://jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp)))?/bbs/link\.cgi\?url=([^"]+)" target="_blank">([^><]+)</a>}i', '$1', $body);
        */

		// リンク外し
		$body = preg_replace('{<a href="(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)</a>}i', '$1', $body);
        
		if ($order == 1) {
			$datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'.$mtitle."\n";
		} else {
			$datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'."\n";
		}
		$mdatlines[$order] = $datline;
		if ($order > $GLOBALS['machi_latest_num']) {
			$GLOBALS['machi_latest_num'] = $order;
		}
	}
	
	return $mdatlines;
}

?>
