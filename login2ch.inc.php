<?php

include_once './conf.inc.php';  // 基本設定
require_once './filectl.class.php';
require_once './crypt_xor.inc.php';

/**
 * ■2chにログインする
 */
function login2ch()
{
	global $_conf, $_info_msg_ht;

	// 2ch●ID, PW設定を読み込む
	if ($array = P2Util::readIdPw2ch()) {
		list($login2chID, $login2chPW, $autoLogin2ch) = $array;

	} else {
		$_info_msg_ht .= "<p>p2 Error: ログインのためのIDとパスワードを登録して下さい。[<a href=\"login2ch.php\" target=\"subject\">2chログイン管理</a>]</p>";
		return false;
	}

	$auth2ch_url = "https://2chv.tora3.net/futen.cgi";
	$postf = "ID=".$login2chID."&PW=".$login2chPW;
	$x_2ch_ua = "X-2ch-UA: ".$_conf['p2name']."/".$_conf['p2version'];
	$dolib2ch = "DOLIB/1.00";
	$tempfile = $_conf['pref_dir']."/p2temp.php";
	
	// 念のためあらかじめtempファイルを除去しておく
	if (file_exists($tempfile)) { unlink($tempfile); }

	$curl_msg = "";
	
	// コマンドCURL優先
	if (empty($_conf['precede_phpcurl'])) {
		if (!$r = getAuth2chWithCommandCurl($login2chID, $login2chPW, $tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch)) {
			$curl_msg .= "「systemのcurlコマンド」で実行失敗。";
			if (!extension_loaded('curl')) {
					$curl_msg .= "「PHPのcurl」は使えないようです";
			} elseif (!$r = getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf)) {
				$curl_msg .= "「PHPのcurl」で実行失敗。";
			}
		}
	
	// PHP CURL優先
	} else {
		if (!extension_loaded('curl')) {
			$curl_msg .= "「PHPのcurl」は使えないようです";
		} elseif (!$r = getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf)) {
			$curl_msg .= "「PHPのcurl」で実行失敗。";
		}
		
		if (empty($r)) {
			if (!$r = getAuth2chWithCommandCurl($login2chID, $login2chPW, $tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch)) {
				$curl_msg .= "「systemのcurlコマンド」で実行失敗。";
			}
		}
	}
	
	/*
	■PHP の fsockopen は https に対応していないので無理なのだ
	$post = array("ID"=>$login2chID, "PW"=>$login2chPW);
	$headers = $x_2ch_ua."\r\n";
	echo $r = getHttpContents($auth2ch_url, "", "POST", $headers, $post, $dolib2ch);
	*/
	
	// 接続失敗ならば
	if (empty($r)) {
		if (file_exists($_conf['idpw2ch_php'])) { unlink($_conf['idpw2ch_php']); }
		if (file_exists($_conf['sid2ch_php'])) { unlink($_conf['sid2ch_php']); }
		
		$_info_msg_ht .= "<p>p2 info: 2ちゃんねるへの●IDログインを行うには、systemでcurlコマンドが使用可能であるか、PHPの<a href=\"http://www.php.net/manual/ja/ref.curl.php\">CURL関数</a>が有効である必要があります。</p>";

		$_info_msg_ht .= "<p>p2 Error: 2chログイン処理に失敗しました。{$curl_msg}</p>";
		return false;
	}
	
	
	
	// tempファイルはすぐに捨てる
	if (file_exists($tempfile)) { unlink($tempfile); }
	
	$r = rtrim($r);
	
	// 分解
	if (preg_match('/SESSION-ID=(.+?):(.+)/', $r, $matches)) {
		$uaMona = $matches[1];
		$SID2ch = $matches[1].':'.$matches[2];
	} else {
		if (file_exists($_conf['sid2ch_php'])) { unlink($_conf['sid2ch_php']); }
		$_info_msg_ht .= "<p>p2 Error: ログイン接続に失敗しました。</p>";
		return false;
	}
	
	// 認証照合失敗なら
	if ($uaMona == "ERROR") {
		if (file_exists($_conf['idpw2ch_php'])) { unlink($_conf['idpw2ch_php']); }
		if (file_exists($_conf['sid2ch_php'])) { unlink($_conf['sid2ch_php']); }
		$_info_msg_ht .= "<p>p2 Error: SESSION-IDの取得に失敗しました。IDとパスワードを確認の上、ログインし直して下さい。</p>";
		return false;
	}

	//echo $r;//
	
	// SIDの記録保持 =======================
	$cont = <<<EOP
<?php
\$uaMona='{$uaMona}';
\$SID2ch='{$SID2ch}';
?>
EOP;
	FileCtl::make_datafile($_conf['sid2ch_php'], $_conf['pass_perm']); // $_conf['sid2ch_php'] がなければ生成
	$fp = @fopen($_conf['sid2ch_php'], "wb");
	if (!$fp) {
		$_info_msg_ht .= "<p>p2 Error: {$_conf['sid2ch_php']} を保存できませんでした。ログイン登録失敗。</p>";
		return false;
	}
	@flock($fp, LOCK_EX);
	fwrite($fp, $cont);
	@flock($fp, LOCK_UN);
	fclose($fp);

	return $SID2ch;
}


/**
 * ■systemコマンドでcurlを実行して、2chログインのSIDを得る
 */
function getAuth2chWithCommandCurl($login2chID, $login2chPW, $tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch)
{
	global $_conf;

	$curlrtn = 1;
	
	// proxyの設定
	if ($_conf['proxy_use']) {
		$with_proxy = " -x ".$_conf['proxy_host'].":".$_conf['proxy_port'];
	} else {
		$with_proxy = "";
	}
	
	// ●「systemコマンドでcurl」（証明書検証あり）を実行
	$curlcmd = "curl -H \"{$x_2ch_ua}\" -A {$dolib2ch} -d ID={$login2chID} -d PW={$login2chPW} -o {$tempfile}{$with_proxy} {$auth2ch_url}";
	system($curlcmd, $curlrtn);
	
	// ●「systemコマンドのcurl」（証明書検証あり）で無理だったなら、（証明書検証なし）で再チャレンジ
	if ($curlrtn != 0) {
		$curlcmd = "curl -H \"{$x_2ch_ua}\" -A {$dolib2ch} -d ID={$login2chID} -d PW={$login2chPW} -o {$tempfile}{$with_proxy} -k {$auth2ch_url}";
		system($curlcmd, $curlrtn);
	}
	
	if ($curlrtn == 0) {
		if ($r = @file_get_contents($tempfile)) {
			return $r;
		}
	}
	
	return false;
}

/**
 * ■PHPのcurlで2chログインのSIDを得る
 */
function getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf)
{
	global $_conf;
	
	// ■PHPのCURLが使えるなら、それでチャレンジ
	if (extension_loaded('curl')) {
		// ●「PHPのcurl」（証明書検証あり）で実行
		execAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, true);
		// ●「PHPのcurl」（証明書検証あり）で無理なら、「PHPのcurl」（証明書検証なし）で再チャレンジ
		if (!@file_get_contents($tempfile)) {
			execAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, false);
		}
		if ($r = @file_get_contents($tempfile)) {
			return $r;
		}
	
	}
	
	return false;
}

/**
 * ■PHPのcurlを実行する
 */
function execAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, $withk = false)
{
	global $_conf;
	
	$ch = curl_init();
	$fp = fopen($tempfile, 'wb');
	@flock($fp, LOCK_EX);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_URL, $auth2ch_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array($x_2ch_ua));
	curl_setopt($ch, CURLOPT_USERAGENT, $dolib2ch);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postf);
	// 証明書の検証をしないなら
	if ($withk) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	}
	// プロキシの設定
	if ($_conf['proxy_use']) {
		curl_setopt($ch, CURLOPT_PROXY, $_conf['proxy_host'].':'.$_conf['proxy_port']);
	}
	curl_exec($ch);
	curl_close($ch);
	@flock($fp, LOCK_UN);
	fclose($fp);
	
	return;
}
?>