<?php

require_once './filectl_class.inc';

/**
* p2 - p2用のユーティリティクラス
* インスタンスを作らずにクラスメソッドで利用する
* 	
* @create  2004/07/15
*/
class P2Util{

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
			$url_t = P2Util::throughIme($wap_req->url);
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

	/**
	 * subject.txtをダウンロードして保存する
	 */
	function subjectDownload($url, $subjectfile)
	{
		global $_conf, $datdir, $_info_msg_ht, $ext_win_target, $fsockopen_time_limit, $proxy;

		$perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;
	
		if (file_exists($subjectfile)) {
			if ($_GET['norefresh'] or isset($_GET['submit_kensaku']) || isset($_POST['submit_kensaku'])) {
				return;	// 更新しない場合は、その場で抜けてしまう
			} elseif ((!$_POST['newthread']) and P2Util::isSubjectFresh($subjectfile)) {
				return;	// 新規スレ立て時でなく、更新が新しい場合も抜ける
			}
			$modified = gmdate("D, d M Y H:i:s", filemtime($subjectfile))." GMT";
		} else {
			$modified = false;
		}

		if (extension_loaded('zlib') and strstr($url, ".2ch.net")){
			$headers = "Accept-Encoding: gzip\r\n";
		}

		// したらばのlivedoor移転に対応。読込先をlivedoorとする。
		$url = P2Util::adjustHostJbbs($url);

		//DL
		include_once("./wap.inc");
		$wap_ua = new UserAgent;
		$wap_ua->setAgent("Monazilla/1.00 (".$_conf['p2name']."/".$_conf['p2version'].")");
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
			$url_t = P2Util::throughIme($wap_req->url);
			$_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
			$_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$ext_win_target}>{$wap_req->url}</a> に接続できませんでした。</div>";
		} else {
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
			chmod($subjectfile, $perm);
		}
	
		return $wap_res;
	}

	/**
	 * ■ subject.txt が新鮮なら true を返す
	 */
	function isSubjectFresh($subjectfile)
	{
		global $_conf;
		if (file_exists($subjectfile)) {	// キャッシュがある場合
			// キャッシュの更新が指定時間以内なら
			if (@filemtime($subjectfile) > time() - $_conf['sb_dl_interval']) {
				return true;
			}
		}
		return false;
	}

	/**
	 * ゲートを通すためのURL変換
	 */
	function throughIme($url)
	{
		global $_conf, $p2ime_url;
	
		if ($_conf['through_ime'] == "2ch") {
			$purl = parse_url($url);
			$url_r = $purl['scheme'] . "://ime.nu/" . $purl['host'] . $purl['path'];
		} elseif ($_conf['through_ime'] == "p2" || $_conf['through_ime'] == "p2pm") {
			$url_r = $p2ime_url . "?url=" . $url;
		} elseif ($_conf['through_ime'] == "p2m") {
			$url_r = $p2ime_url . "?m=1&amp;url=" . $url;
		} else {
			$url_r = $url;
		}
		return $url_r;
	}

	/**
	 * ■ host が 2ch or bbspink なら true を返す
	 */
	function isHost2chs($host)
	{
		if (preg_match("/\.(2ch\.net|bbspink\.com)/", $host)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * ■ host が be.2ch.net なら true を返す
	 */
	function isHostBe2chNet($host)
	{
		if (preg_match("/^be\.2ch\.net/", $host)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * ■ host が bbspink なら true を返す
	 */
	function isHostBbsPink($host)
	{
		if (preg_match("/\.bbspink\.com/", $host)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * ■ host が machibbs なら true を返す
	 */
	function isHostMachiBbs($host)
	{
		if (preg_match("/\.(machibbs\.com|machi\.to)/", $host)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * ■ host が machibbs.net まちビねっと なら true を返す
	 */
	function isHostMachiBbsNet($host)
	{
		if (preg_match("/\.(machibbs\.net)/", $host)) {
			return true;
		} else {
			return false;
		}
	}
		
	/**
	 * ■ host が JBBS@したらば なら true を返す
	 */
	function isHostJbbsShitaraba($in_host)
	{
		if (preg_match("/jbbs\.shitaraba\.com|jbbs\.livedoor\.com|jbbs\.livedoor\.jp/", $in_host)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * ■JBBS@したらばのホスト名変更に対応して変更する
	 *
	 * @param	string	$in_str	ホスト名でもURLでもなんでも良い
	 */
	function adjustHostJbbs($in_str)
	{
		if (preg_match("/jbbs\.shitaraba\.com|jbbs\.livedoor\.com/", $in_str)) {
			$str = preg_replace("/jbbs\.shitaraba\.com|jbbs\.livedoor\.com/", "jbbs.livedoor.jp", $in_str, 1);
		} else {
			$str = $in_str;
		}
		return $str;
	}


	/**
	 * ■データphp形式のファイルを読み込む
	 */
	function fileDataPhp($data_php)
	{
		if (!$lines = @file($data_php)) {
			return $lines;
		
		} else {
			// 最初の行はphpの開始行なので飛ばす
			@array_shift($lines);
			// 最後の行もphpの閉じたタグなのでカットする
			@array_pop($lines);
			return $lines;
		}
	}

	/**
	 * ■データphp形式のデータをエスケープする
	 */
	function escapeDataPhp($str)
	{
		// &<>/ → &xxx; のエスケープをする
		$str = str_replace("&", "&amp;", $str);	
		$str = str_replace("<", "&lt;", $str);
		$str = str_replace(">", "&gt;", $str);
		$str = str_replace("/", "&frasl;", $str);
		return $str;
	}

	/**
	 * ■データphp形式のデータをアンエスケープする
	 */
	function unescapeDataPhp($str)
	{
		// &<>/ → &xxx; のエスケープを元に戻す
		$str = str_replace("&lt;", "<", $str);
		$str = str_replace("&gt;", ">", $str);
		$str = str_replace("&frasl;", "/", $str);
		$str = str_replace("&amp;", "&", $str);	
		return $str;
	}
	
	/**
	 * ■旧形式の書き込み履歴を新形式に変換する
	 */
	function transResHistLog()
	{
		global $prefdir, $res_write_rec, $res_write_perm;

		$rh_dat_php = $prefdir."/p2_res_hist.dat.php";
		$rh_dat = $prefdir."/p2_res_hist.dat";

		// 書き込み履歴を記録しない設定の場合は何もしない
		if ($res_write_rec == 0) {
			return true;
		}

		// p2_res_hist.dat.php（新） がなくて、p2_res_hist.dat（旧） が読み込み可能であったら		
		if ((!file_exists($rh_dat_php)) and is_readable($rh_dat)) {
			// 読み込んで
			if($cont = FileCtl::get_file_contents($rh_dat)) {
				// <>区切りからタブ区切りに変更する
				// まずタブを全て外して
				$cont = str_replace("\t", "", $cont);
				// <>をタブに変換して
				$cont = str_replace("<>", "\t", $cont);
				
				// &<>/ を &xxx; にエスケープして
				$cont = P2Util::escapeDataPhp($cont);
				
				// 先頭文と末文を追加
				$cont = "<?php /*\n".$cont."*/ ?>\n";
				
				// p2_res_hist.dat.php として保存
				FileCtl::make_datafile($rh_dat_php, $res_write_perm);
				// 書き込む
				$fp = @fopen($rh_dat_php, "wb") or die("Error: {$rh_dat_php} を更新できませんでした");
				flock($fp, LOCK_EX);
				fputs($fp, $cont);
				flock($fp, LOCK_UN);
				fclose($fp);
			}
		}
		return true;
	}

	/**
	 * ■前回のアクセス情報を取得
	 */
	function getLastAccessLog($logfile)
	{
		// 読み込んで
		if (!$lines = P2Util::fileDataPhp($logfile)) {
			return false;
		}
		if (!isset($lines[1])) {
			return false;
		}
		$line = P2Util::unescapeDataPhp($lines[1]);
		$lar = explode("\t", $line);
		
		$alog['user'] = $lar[6];
		$alog['date'] = $lar[0];
		$alog['ip'] = $lar[1];
		$alog['host'] = $lar[2];
		$alog['ua'] = $lar[3];
		$alog['referer'] = $lar[4];
		
		return $alog;
	}
	
	
	/**
	 * ■アクセス情報をログに記録する
	 */
	function recAccessLog($logfile, $maxline="100")
	{
		global $res_write_perm, $login;
		
		// 変数設定
		$date = date("Y/m/d (D) G:i:s");
	
		// HOSTを取得
		if (!$remoto_host = $_SERVER['REMOTE_HOST']) {
			$remoto_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		}
		if ($remoto_host == $_SERVER['REMOTE_ADDR']) {
			$remoto_host = "";
		}

		if (isset($login['user'])) {
			$user = $login['user'];
		} else {
			$user = "";
		}
		
		// 新しいログ行を設定
		$newdata = $date."<>".$_SERVER['REMOTE_ADDR']."<>".$remoto_host."<>".$_SERVER['HTTP_USER_AGENT']."<>".$_SERVER['HTTP_REFERER']."<>".""."<>".$user."\n";
		//$newdata = htmlspecialchars($newdata);


		// まずタブを全て外して
		$newdata = str_replace("\t", "", $newdata);
		// <>をタブに変換して
		$newdata = str_replace("<>", "\t", $newdata);
				
		// &<>/ を &xxx; にエスケープして
		$newdata = P2Util::escapeDataPhp($newdata);

		//■書き込み処理
		FileCtl::make_datafile($logfile, $res_write_perm); // なければ生成

		// ログファイルの中身を取得する
		if (!$lines = P2Util::fileDataPhp($logfile)) {
			$lines = array();
		} else {
			// 制限行調整
			while (sizeof($lines) > $maxline -1) {
				array_pop($lines);
			}
		}

		// 新しいデータを一番上に追加
		@array_unshift($lines, $newdata);
		// 先頭文を追加
		@array_unshift($lines, "<?php /*\n");
		// 末文を追加
		@array_push($lines, "*/ ?>\n");

		// 書き込む
		$fp = @fopen($logfile, "wb") or die("Error: {$logfile} を更新できませんでした");
		flock($fp, LOCK_EX);
		$i = 1;
		foreach ($lines as $l) {
			fputs($fp, $l);
		}
		flock($fp, LOCK_UN);
		fclose($fp);

		return true;
	}

	/**
	 * ■ブラウザがSafari系ならtrueを返す
	 */
	function isBrowserSafariGroup()
	{
		if (strstr($_SERVER['HTTP_USER_AGENT'], 'Safari') || strstr($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit') || strstr($_SERVER['HTTP_USER_AGENT'], 'Konqueror')) {
			return true;
		} else {
			return false;
		}
	}
}

?>
