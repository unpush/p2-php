<?php

require_once("./conf.php");  //基本設定
require_once './filectl.class.php';
require_once("./datactl.inc");
require_once("./crypt_xor.inc");

/**
 * ■2chにログインする
 */
function login2ch()
{
	global $_conf, $prefdir, $idpw2ch_php, $sid2ch_php, $proxy;
	global $_info_msg_ht, $pass_perm, $crypt_xor_key;

	$php_curl_done_flag = false;
	
	// ■2ch ID&PW 読み込み
	if (file_exists($idpw2ch_php)) {
		include($idpw2ch_php);
		$login2chPW = base64_decode($login2chPW);
		$login2chPW = decrypt_xor($login2chPW, $crypt_xor_key);
	} else {
		$_info_msg_ht .= "<p>p2 Error: ログインのためのIDとパスワードを登録して下さい。[<a href=\"login2ch.php\" target=\"subject\">2chログイン管理</a>]</p>";
		return false;
	}

	$auth2ch_url = 'https://2chv.tora3.net/futen.cgi';
	$postf = "ID=".$login2chID."&PW=".$login2chPW;
	$x_2ch_ua = "X-2ch-UA: ".$_conf['p2name']."/".$_conf['p2version'];
	$dolib2ch = "DOLIB/1.00";
	$tempfile = $prefdir."/p2temp.php";
	
	// 念のためあらかじめtempファイルを除去しておく
	if( file_exists($tempfile) ){ unlink($tempfile); }
	

	// ■systemコマンドでcurlを実行
	$curlrtn=1;
	// proxyの設定
	if($proxy['use']){
		$with_proxy = " -x ".$proxy['host'].":".$proxy['port'];
	}else{
		$with_proxy = "";
	}
	// ●「systemコマンドでcurl」（証明書検証あり）を実行
	$curlcmd = "curl -H \"{$x_2ch_ua}\" -A {$dolib2ch} -d ID={$login2chID} -d PW={$login2chPW} -o {$tempfile}{$with_proxy} {$auth2ch_url}";
	system($curlcmd, $curlrtn);
	
	// ●「systemコマンドのcurl」（証明書検証あり）で無理だったなら、（証明書検証なし）で再チャレンジ
	if($curlrtn!=0){
		$curlcmd = "curl -H \"{$x_2ch_ua}\" -A {$dolib2ch} -d ID={$login2chID} -d PW={$login2chPW} -o {$tempfile}{$with_proxy} -k {$auth2ch_url}";
		system($curlcmd, $curlrtn);
	}
		
	// ■systemコマンドのcurlで無理だったなら
	if($curlrtn!=0){
	
		// ■PHPのCURLが使えるなら、それでチャレンジ
		if( extension_loaded('curl') ){
			// ●「PHPのcurl」（証明書検証あり）で実行
			getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, true);
			// ●「PHPのcurl」（証明書検証あり）で無理なら、「PHPのcurl」（証明書検証なし）で再チャレンジ
			if(! file_exists($tempfile) ){
				getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, false);
			}
			$php_curl_done_flag = true;	// PHPのCURLで実行したよ、というフラグ
			
		}else{
			$_info_msg_ht .= "<p>p2 info: 2ちゃんねるへの●IDログインを行うには、systemでcurlコマンドが使用可能であるか、PHPの<a href=\"http://www.php.net/manual/ja/ref.curl.php\">CURL関数</a>が有効である必要があります。</p>";
		}
	}

	
	/*
	■PHP の fsockopen は https に対応していないので無理なのだ
	$post=array("ID"=>$login2chID, "PW"=>$login2chPW);
	$headers=$x_2ch_ua."\r\n";
	echo $r=getHttpContents($auth2ch_url, "", "POST", $headers, $post, $dolib2ch);
	*/
	
	if(! $r = FileCtl::get_file_contents($tempfile) ){
		if( file_exists($sid2ch_php) ){ unlink($sid2ch_php); }
		if( $php_curl_done_flag ){
			$setsumei = "（「systemのcurlコマンド」「PHPのcurl」の両方とも実行失敗しました）";
		}else{
			$setsumei = "（「systemのcurlコマンド」で実行失敗。「PHPのcurl」は使えないようです）";
		} 
		$_info_msg_ht .= "<p>p2 Error: 2chログイン処理に失敗しました。{$setsumei}</p>";
		return false;
	}
	
	// tempファイルはすぐに捨てる
	if( file_exists($tempfile) ){ unlink($tempfile); }
	
	$r=rtrim($r);
	
	// 分解
	if( preg_match("/SESSION-ID=(.+?):(.+)/", $r, $matches) ){
		$uaMona = $matches[1];
		$SID2ch = $matches[1].":".$matches[2];
	}else{
		if( file_exists($sid2ch_php) ){ unlink($sid2ch_php); }
		$_info_msg_ht .= "<p>p2 Error: ログイン接続に失敗しました。</p>";
		return false;
	}
	
	if($uaMona=="ERROR"){
		if( file_exists($idpw2ch_php) ){ unlink($idpw2ch_php); }
		if( file_exists($sid2ch_php) ){ unlink($sid2ch_php); }
		$_info_msg_ht .= "<p>p2 Error: SESSION-IDの取得に失敗しました。IDとパスワードを確認の上、ログインし直して下さい。</p>";
		return false;
	}

	//echo $r;//
	
	// SIDの記録保持 =======================
	$cont=<<<EOP
<?php
\$uaMona='{$uaMona}';
\$SID2ch='{$SID2ch}';
?>
EOP;
	FileCtl::make_datafile($sid2ch_php, $pass_perm); //$sid2ch_php がなければ生成
	$fp = @fopen($sid2ch_php,"w");
	if(!$fp){
		$_info_msg_ht .= "<p>p2 Error: {$sid2ch_php} を保存できませんでした。ログイン登録失敗。</p>";
		return false;
	}
	fwrite($fp,$cont);
	fclose($fp);

	return $SID2ch;
}

//===================================================
// ■PHPのcurlで2chログインのSIDを得る関数
//===================================================
function getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, $withk=false){
	global $proxy;
	
	$ch = curl_init();
	$fp = fopen ($tempfile, "w");
	curl_setopt ($ch, CURLOPT_FILE, $fp);
	curl_setopt ($ch, CURLOPT_URL, $auth2ch_url);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_HTTPHEADER, array($x_2ch_ua));
	curl_setopt ($ch, CURLOPT_USERAGENT, $dolib2ch);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postf);
	if($withk){	// 証明書の検証をしないなら
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	}
	if($proxy['use']){
		curl_setopt ($ch, CURLOPT_PROXY, $proxy['host'].':'.$proxy['port']);
	}
	curl_exec ($ch);
	curl_close ($ch);
	fclose ($fp);
}

?>