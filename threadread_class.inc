<?php
// p2 - スレッド リード クラス

require_once './filectl.class.php';
require_once './p2util.class.php';	// p2用のユーティリティクラス

/**
 * スレッドリードクラス
 */
class ThreadRead extends Thread{

	var $datlines; // datから読み込んだラインを格納する配列
	
	var $resrange; // array( 'start' => i, 'to' => i, 'nofirst' => bool )
	
	var $onbytes; // サーバから取得したdatサイズ
	var $diedat; // サーバからdat取得ができなければtrue
	var $onthefly; // ローカルにdat保存しないオンザフライ読み込みならtrue

	var $idcount;	// 配列。key は ID記号, value は ID出現回数
	
	var $getdat_error_msg_ht;	// dat取得に失敗した時に表示されるメッセージ（HTML）

	/*
	 * ■コンストラクタ
	 */
	function ThreadRead()
	{
		$getdat_error_msg_ht = "";
	}

	/**
	 * DATをダウンロードする
	 */
	function downloadDat()
	{
		global $_conf, $uaMona, $SID2ch, $dat_response_msg;
		
		// まちBBS
		if (P2Util::isHostMachiBbs($this->host)) {
			include_once("read_machibbs.inc");
			machiDownload();
		// JBBS@したらば
		} elseif (P2Util::isHostJbbsShitaraba($this->host)) {
			include_once("read_machibbs.inc");
			machiDownload();
		} else {
			$this->getDatBytesFromLocalDat(); // $aThread->length をset

			//2ch bbspink●読み
			if (P2Util::isHost2chs($this->host) and $_GET['maru']) {
				//ログインしてなければ or ログイン後、24時間以上経過していたら自動再ログイン
				if ((!file_exists($_conf['sid2ch_php']) or $_REQUEST['relogin2ch']) or (@filemtime($sid2ch_php) < time() - 60*60*24)) {
					include_once("./login2ch.inc");
					if (!login2ch()) {
						$dat_response_msg = $this->get2chDatError();
						$this->diedat = true;
						return false;
					}
				}

				include($_conf['sid2ch_php']);
				$this->downloadDat2chMaru();

			// 2chの過去ログ倉庫読み
			} elseif ($_GET['kakolog'] && $_GET['kakoget']) {
				if ($_GET['kakoget'] == 1) {
					$ext = ".dat.gz";
				} elseif ($_GET['kakoget'] == 2) {
					$ext = ".dat";
				}
				$this->downloadDat2chKako(urldecode($_GET['kakolog']), $ext);
			// 2ch or 2ch互換
			} else {
				$this->downloadDat2ch($this->length); // 差分DLする
			}

		}
	
	}
	
	/**
	 * 2ch用 DATをダウンロードする
	 */
	function downloadDat2ch($from_bytes)
	{
		global $_conf, $dat_response_msg, $ls, $fsockopen_time_limit, $_info_msg_ht, $dat_perm;
		global $debug, $prof;
	
		if(!($this->host && $this->bbs && $this->key)){return false;}

		if($from_bytes==""){$from_bytes=0;}
		if($from_bytes==0){
			$mode="w";
			$zero_read=true;
		}else{
			$mode="a";
			$from_bytes=$from_bytes-1;
		}
		
		$method = "GET";
		if (!$uaMona) {$uaMona = "Monazilla/1.00";}
		$p2ua = $uaMona." (".$_conf['p2name']."/".$_conf['p2version'].")";
		
		$url = "http://" . $this->host . "/{$this->bbs}/dat/{$this->key}.dat";
		//$url="http://news2.2ch.net/test/read.cgi?bbs=newsplus&key=1038486598";

		$URL = parse_url($url); //URL分解
		if (isset($URL['query'])) { //クエリー
		    $URL['query'] = "?".$URL['query'];
		} else {
		    $URL['query'] = "";
		}

		//プロキシ
		if ($_conf['proxy_use']) {
			$send_host = $_conf['proxy_host'];
			$send_port = $_conf['proxy_port'];
			$send_path = $url;
		} else {
			$send_host = $URL['host'];
			$send_port = $URL['port'];
			$send_path = $URL['path'].$URL['query'];
		}
		
		if (!$send_port){$send_port = 80;}//デフォルトを80
			
		$request = $method." ".$send_path." HTTP/1.0\r\n";
		$request .= "Host: ".$URL['host']."\r\n";
		$request .= "Accept: */*\r\n";
		//$request .= "Accept-Charset: Shift_JIS\r\n";
		//$request .= "Accept-Encoding: gzip, deflate\r\n";
		$request .= "Accept-Language: ja, en\r\n";
		$request .= "User-Agent: ".$p2ua."\r\n";
		if(!$zero_read){$request .= "Range: bytes={$from_bytes}-\r\n";}
		$request .= "Referer: http://{$URL['host']}/{$this->bbs}/\r\n";
		$request .= "Connection: Close\r\n";

		if($this->modified){
			$request .= "If-Modified-Since: ".$this->modified."\r\n";
		}
	
		$request .= "\r\n";
		
		/* WEBサーバへ接続 */
		$fp = fsockopen($send_host, $send_port, $errno, $errstr, $fsockopen_time_limit);
		if (!$fp) {
			$url_t = P2Util::throughIme($url);
			$_info_msg_ht .= "<p>サーバ接続エラー: {$errstr} ({$errno})<br>p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target']}>{$url}</a> に接続できませんでした。</p>";
			$this->diedat=true; return false;
		}
		$wr = "";
		fputs($fp, $request);

		while (!feof($fp)){

			if($start_here){

				if($code=="200" || $code=="206"){
					
					while( !feof($fp) ){
						$wr .= fread($fp, 4096);
					}
					
					if(!$zero_read){ //末尾の改行であぼーんチェック
						if(substr($wr, 0, 1)!="\n"){
							//echo "あぼーん検出";
							fclose ($fp);
							unset($this->onbytes);
							unset($this->modified);
							return $this->downloadDat2ch(0); //あぼーん検出。全部取り直し。
						}
						$wr=substr($wr, 1);
					}
					FileCtl::make_datafile($this->keydat, $dat_perm);
					$fdat = fopen($this->keydat, $mode);
					fwrite($fdat, $wr);
					fclose ($fdat);
					//echo $wr."<br>";// for debug
					
					$debug && $prof->startTimer( "dat_size_check" );
					//取得後サイズチェック
					if( $zero_read==false && $this->onbytes){
						$this->getDatBytesFromLocalDat(); //$aThread->length をset
						if($this->onbytes != $this->length ){
							fclose ($fp);
							unset($this->onbytes);
							unset($this->modified);
							$_info_msg_ht .= "p2 info: $this->onbytes/$this->length ファイルサイズが変なので、datを再取得<br>";
							return $this->downloadDat2ch(0); //datサイズは不正。全部取り直し。
						}elseif($this->onbytes == $this->length){ //サイズが同じならそのまま
							fclose ($fp);
							$this->isonline=true;
							return;
						}
					}
					$debug && $prof->stopTimer( "dat_size_check" );
				
				// スレッドがないと判断
				} else {
					fclose ($fp);
					return $this->downloadDat2chNotFound();
				}
				
			} else {
				$l = fgets($fp,32800);
				if( preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches) ){ // ex) HTTP/1.1 304 Not Modified
					$code = $matches[1];
					
					if($code=="200" || $code=="206"){ //Partial Content
						;
					}elseif( $code=="304" ){ //Not Modified
						fclose ($fp);
						$this->isonline=true;
						return "304 Not Modified";
					}elseif( $code=="416" ){ // Requested Range Not Satisfiable				
						//echo "あぼーん検出";
						fclose ($fp);
						unset($this->onbytes);
						unset($this->modified);
						return $this->downloadDat2ch(0); // あぼーん検出。全部取り直し。
					}else{
						fclose ($fp);
						return $this->downloadDat2chNotFound();							
					}
				}
				
				if($zero_read){
					if( preg_match("/^Content-Length: ([0-9]+)/", $l, $matches) ){
						$this->onbytes = $matches[1];
					}
				}else{

					if( preg_match("/^Content-Range: bytes ([^\/]+)\/([0-9]+)/", $l, $matches) ){
						$this->onbytes = $matches[2];
					}
					
				}
					
				if( preg_match("/^Last-Modified: (.+)\r\n/", $l, $matches) ){
					//echo $matches[1]."<br>"; //debug
					$this->modified = $matches[1];
			
				}elseif($l=="\r\n"){
					$start_here=true;
				}
			}	
		}
		fclose($fp);
		$this->isonline=true;
		return true;
	}
	
	function downloadDat2chNotFound(){
		global $dat_response_msg;
		if( P2Util::isHost2chs($this->host) ){ //2ch, bbspink ならread.cgiで確認
			$dat_response_msg = $this->get2chDatError();
		}
		$this->diedat=true;
		return false;		
	}
	
	/**
	 * 2ch●用 DATをダウンロードする
	 */
	function downloadDat2chMaru()
	{
		global $_conf, $dat_response_msg, $uaMona, $SID2ch, $fsockopen_time_limit, $_info_msg_ht, $dat_perm;

		if (!($this->host && $this->bbs && $this->key)) {return false;}
		
		unset($datgz_attayo, $start_here, $isGzip, $done_gunzip, $marudatlines, $code);
		
		$method = "GET";
		$p2ua = $uaMona." (".$_conf['p2name']."/".$_conf['p2version'].")";
		
		//  GET /test/offlaw.cgi?bbs=板名&key=スレッド番号&sid=セッションID HTTP/1.1
		$SID2ch = urlencode($SID2ch);
		$url = "http://" . $this->host . "/test/offlaw.cgi/{$this->bbs}/{$this->key}/?raw=0.0&sid={$SID2ch}";

		$URL = parse_url($url); // URL分解
		if (isset($URL['query'])) { // クエリー
		    $URL['query'] = "?".$URL['query'];
		} else {
		    $URL['query'] = "";
		}
	
		//プロキシ
		if ($_conf['proxy_use']) {
			$send_host = $_conf['proxy_host'];
			$send_port = $_conf['proxy_port'];
			$send_path = $url;
		} else {
			$send_host = $URL['host'];
			$send_port = $URL['port'];
			$send_path = $URL['path'].$URL['query'];
		}
		
		if (!$send_port){$send_port = 80;}//デフォルトを80

		$request = $method." ".$send_path." HTTP/1.0\r\n";
		$request .= "Host: ".$URL['host']."\r\n";
		$request .= "Accept-Encoding: gzip, deflate\r\n";
		//$request .= "Accept-Language: ja, en\r\n";
		$request .= "User-Agent: ".$p2ua."\r\n";
		//$request .= "X-2ch-UA: ".$_conf['p2name']."/".$_conf['p2version']."\r\n";
		//$request .= "Range: bytes={$from_bytes}-\r\n";
		$request .= "Connection: Close\r\n";
		if($modified){$request .= "If-Modified-Since: $modified\r\n";}
		$request .= "\r\n";
		
		/* WEBサーバへ接続 */
		$fp = fsockopen($send_host, $send_port, $errno, $errstr, $fsockopen_time_limit);
		if (!$fp) {
			$url_t = P2Util::throughIme($url);
			$_info_msg_ht .= "<p>サーバ接続エラー: {$errstr} ({$errno})<br>p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target']}>{$url}</a> に接続できませんでした。</p>";
			$this->diedat = true;
			return false;
		}
		
		fputs($fp, $request);
		$body = "";
		while (!feof($fp)) {

			if($start_here){

				if($code=="200"){

					while (!feof($fp)){
						$body .= fread($fp, 4096);
					}

					
					if($isGzip){
						$gztempfile=$this->keydat.".gz";
						FileCtl::mkdir_for($gztempfile);
						$ftemp = fopen($gztempfile, "wb") or die("Error: {$gztempfile} を更新できませんでした");
						fwrite($ftemp, $body);
						fclose ($ftemp);
						if(extension_loaded('zlib') ){
							$body = FileCtl::get_gzfile_contents($gztempfile);
						}else{
							if(file_exists($this->keydat)){ //既に存在するなら一時バックアップ退避
								if(file_exists($this->keydat.".bak")){unlink($this->keydat.".bak");}
								rename($this->keydat, $this->keydat.".bak");
							}
							$rcode=1;
							system("gzip -d $gztempfile", $rcode); //解凍
							if($rcode!=0){
								if(file_exists($this->keydat.".bak")){
									if(file_exists($this->keydat)){unlink($this->keydat);}
									rename($this->keydat.".bak", $this->keydat); //失敗ならバックアップ戻す
								}
								$dat_response_msg="<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みは、PHPの<a href=\"http://www.php.net/manual/ja/ref.zlib.php\">zlib拡張モジュール</a>がないか、systemでgzipコマンドが使用可能でなければできません。</p>";
								if( file_exists($gztempfile) ){ unlink($gztempfile); } //gztempファイルを捨てる
								$this->diedat=true;
								return false;
							}else{
								if(file_exists($this->keydat.".bak")){unlink($this->keydat.".bak");}
								$done_gunzip=true;
							}

						}
						if( file_exists($gztempfile) ){ unlink($gztempfile); } //tempファイルを捨てる
					}
					
					if(!$done_gunzip){
						FileCtl::make_datafile($this->keydat, $dat_perm);
						$fdat = fopen($this->keydat, "w");
						fwrite($fdat, $body);
						fclose ($fdat);
					}
					
					//クリーニング=====
					$marudatlines=@file($this->keydat);
					if($marudatlines){
						$firstline=array_shift($marudatlines);
						if(! strstr($firstline, "+OK") ){ // チャンクとか
							$secondline = array_shift($marudatlines);
						}
						FileCtl::make_datafile($this->keydat, $dat_perm);
						$fdat = fopen($this->keydat, "w");
						foreach($marudatlines as $aline){
							if($chunked){ //チャンクエンコーディングが欲しいところ(HTTP 1.0でしのぐ)
								fwrite($fdat, $aline);
							}else{
								fwrite($fdat, $aline);
							}
						}
						fclose ($fdat);
					}
					
					
				}else{ // dat.gzはなかったと判断
					fclose ($fp);
					return $this->downloadDat2chMaruNotFound();
				}
				
			}else{
				$l = fgets($fp,128000);
				//echo $l."<br>";// for debug
				if( preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches) ){ // ex) HTTP/1.1 304 Not Modified
					$code = $matches[1];
					
					if($code=="200"){
						;
					}elseif( $code=="304" ){
						fclose ($fp);
						$this->isonline=true;
						return "304 Not Modified";
					}else{
						fclose ($fp);
						return $this->downloadDat2chMaruNotFound();							
					}
				
				}elseif( preg_match("/^Content-Encoding: (x-)?gzip/", $l, $matches) ){
					$isGzip = true;
				}elseif( preg_match("/^Last-Modified: (.+)\r\n/", $l, $matches) ){
					$lastmodified = $matches[1];
				}elseif( preg_match("/^Content-Length: ([0-9]+)/", $l, $matches) ){
					$onbytes = $matches[1];
				}elseif( preg_match("/^Transfer-Encoding: (.+)\r\n/", $l, $matches) ){ //Transfer-Encoding: chunked
					$t_enco = $matches[1];
					if($t_enco == "chunked"){
						$chunked = true;
					}
				}elseif($l=="\r\n"){
					$start_here = true;
				}
			}
			
		}
		fclose ($fp);
		$this->isonline=true;
		return true;
	}

	function downloadDat2chMaruNotFound()
	{
		global $_conf, $dat_response_msg;
		if (empty($_REQUEST['relogin2ch'])) {
			$_REQUEST['relogin2ch'] = true;
			return $this->downloadDat();
		} else {
			$remarutori_ht="<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$ls}&amp;maru=true&amp;relogin2ch=true\">再取得を試みる</a>";
			$dat_response_msg="<p>p2 info - ●IDでのスレッド取得に失敗しました。[{$remarutori_ht}]</p>";
			$this->diedat=true;
			return false;
		}
	}
	
	/**
	 * 2chの過去ログ倉庫からdat.gzをダウンロード＆解凍する
	 */
	function downloadDat2chKako($uri, $ext)
	{
		global $_conf, $dat_response_msg, $fsockopen_time_limit, $_info_msg_ht, $dat_perm;

		$url = $uri.$ext;
	
		$method = "GET";
		if (!$httpua) {
			$httpua = "Monazilla/1.00 (".$_conf['p2name']."/".$_conf['p2version'].")";
		}
		
		$URL = parse_url($url); // URL分解
		if (isset($URL['query'])) { // クエリー
		    $URL['query'] = "?".$URL['query'];
		} else {
		    $URL['query'] = "";
		}
	
		// プロキシ
		if ($_conf['proxy_use']) {
			$send_host = $_conf['proxy_host'];
			$send_port = $_conf['proxy_port'];
			$send_path = $url;
		} else {
			$send_host = $URL['host'];
			$send_port = $URL['port'];
			$send_path = $URL['path'].$URL['query'];
		}
		if (!$send_port) {$send_port = 80;}//デフォルトを80
	
		$request = $method." ".$send_path." HTTP/1.0\r\n";
		$request .= "Host: ".$URL['host']."\r\n";
		$request .= "User-Agent: ".$httpua."\r\n";
		$request .= "Connection: Close\r\n";
		//$request .= "Accept-Encoding: gzip\r\n";
		if($modified){
			$request .= "If-Modified-Since: $modified\r\n";
		}
		$request .= "\r\n";
	
		/* WEBサーバへ接続 */
		$fp = fsockopen($send_host, $send_port, $errno, $errstr, $fsockopen_time_limit);
		if (!$fp) {
			$url_t = P2Util::throughIme($url);
			echo "<p>サーバ接続エラー: $errstr ($errno)<br>p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target']}>$url</a> に接続できませんでした。</p>";
			$this->diedat = true;
			return false;
		}

		fputs($fp, $request);
		$body = "";
		while (!feof($fp)) {
		
			if($start_here){
			
				if($code=="200"){
					
					while (!feof($fp)){
						$body .= fread($fp, 4096);
					}
					
					if($isGzip){
						$gztempfile=$this->keydat.".gz";
						FileCtl::mkdir_for($gztempfile);
						$ftemp = fopen($gztempfile, "wb") or die("Error: $gztempfile を更新できませんでした");
						fwrite($ftemp, $body);
						fclose ($ftemp);
						if(extension_loaded('zlib') ){
							$body = FileCtl::get_gzfile_contents($gztempfile);
						}else{
							if(file_exists($this->keydat)){ //既に存在するなら一時バックアップ退避
								if(file_exists($this->keydat.".bak")){unlink($this->keydat.".bak");}
								rename($this->keydat, $this->keydat.".bak");
							}
							$rcode=1;
							system("gzip -d $gztempfile", $rcode); //解凍
							if($rcode!=0){
								if(file_exists($this->keydat.".bak")){
									if(file_exists($this->keydat)){unlink($this->keydat);}
									rename($this->keydat.".bak", $this->keydat); //失敗ならバックアップ戻す
								}
								$dat_response_msg="<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みは、PHPの<a href=\"http://www.php.net/manual/ja/ref.zlib.php\">zlib拡張モジュール</a>がないか、systemでgzipコマンドが使用可能でなければできません。</p>";
								if( file_exists($gztempfile) ){ unlink($gztempfile); } //gztempファイルを捨てる
								$this->diedat=true;
								return false;
							}else{
								if(file_exists($this->keydat.".bak")){unlink($this->keydat.".bak");}
								$done_gunzip=true;
							}

						}
						if( file_exists($gztempfile) ){ unlink($gztempfile); } //tempファイルを捨てる
					}

					if(!$done_gunzip){
						FileCtl::make_datafile($this->keydat, $dat_perm);
						$fdat = fopen($this->keydat, "w");
						fwrite($fdat, $body);
						fclose ($fdat);
					}
					
				}else{ // なかったと判断
					fclose ($fp);
					return $this->downloadDat2chKakoNotFound($uri, $ext);
				
				}

			}else{
				$l = fgets($fp,128000);
				if( preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches) ){ // ex) HTTP/1.1 304 Not Modified
					$code = $matches[1];
					
					if($code=="200"){
						;
					}elseif( $code=="304" ){
						fclose ($fp);
						$this->isonline=true;
						return "304 Not Modified";
					}else{
						fclose ($fp);
						return $this->downloadDat2chKakoNotFound($uri, $ext);
					}

				}elseif( preg_match("/^Content-Encoding: (x-)?gzip/", $l, $matches) ){
					$isGzip=true;
				}elseif( preg_match("/^Last-Modified: (.+)\r\n/", $l, $matches) ){
					$lastmodified = $matches[1];
				}elseif( preg_match("/^Content-Length: ([0-9]+)/", $l, $matches) ){
					$onbytes = $matches[1];
				}elseif($l=="\r\n"){
					$start_here=true;
				}
			}
			
		}
		fclose ($fp);
		$this->isonline=true;
		return true;
	}
	
	/**
	 * downloadDat2chKakoNotFound
	 */
	function downloadDat2chKakoNotFound($uri, $ext)
	{
		global $_conf, $dat_response_msg;
					
		if ($ext == ".dat.gz") {
			return $this->downloadDat2chKako($uri, ".dat"); //.dat.gzがなかったら.datでもう一度
		}
		if ($_GET['kakolog']) {
			$kakolog_ht = "<p><a href=\"{$_GET['kakolog']}.html\"{$_conf['bbs_win_target']}>{$_GET['kakolog']}.html</a></p>";
		}
		$dat_response_msg = "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みに失敗しました。</p>";
		$dat_response_msg .= $kakolog_ht;
		$this->diedat = true;
		return false;

	}
	
	/**
	 * 2chのdatを取得できなかった原因を返す
	 */
	function get2chDatError()
	{
		global $_conf, $ls;
		global $fsockopen_time_limit, $_info_msg_ht;
	
		$read_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}";
		
		// read.cgi 取得 ====================
		include_once("./wap.inc");
		$wap_ua = new UserAgent;
		$wap_ua->setAgent($_conf['p2name']."/".$_conf['p2version']); // ここは、"Monazilla/" をつけるとNG
		$wap_ua->setTimeout($fsockopen_time_limit);
		$wap_req = new Request;
		$wap_req->setUrl($read_url);
		if ($_conf['proxy_use']) {
			$wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
		}
		$wap_res = $wap_ua->request($wap_req);
		
		if ($wap_res->is_error()) {
			$url_t = P2Util::throughIme($wap_req->url);
			$_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
			$_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target']}>{$wap_req->url}</a> に接続できませんでした。</div>";
			$read_response = false;
		} else {
			$read_response = $wap_res->content;
		}
		unset($wap_ua, $wap_req, $wap_res);		
		//=============================
		
		$kakosoko_match = "/このスレッドは過去ログ倉庫に格.{1,2}されています/";
		$naidesu_match="/<title>そんな板orスレッドないです。<\/title>/";
		
		//<a href="../../../../mac/kako/1004/10046/1004680972.html">
		$kakohtml_match="/<a href=\"\.\.\/\.\.\/\.\.\/\.\.\/([^\/]+\/kako\/\d+(\/\d+)?\/(\d+)).html\">/";
		$waithtml_match="/html化されるのを待っているようです。/";
		
		if(preg_match($kakosoko_match, $read_response, $matches)){ //<title>がこのスレッドは過去ログ倉庫に
			$dat_response_status="このスレッドは過去ログ倉庫に格納されています。";
			//if(file_exists($_conf['idpw2ch_php']) || file_exists($_conf['sid2ch_php'])){
				$marutori_ht="<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$ls}&amp;maru=true\">●IDでp2に取り込む</a>";
			//}else{
			//	$marutori_ht="<a href=\"login2ch.php\" target=\"subject\">●IDログイン</a>";
			//}
			$dat_response_msg="<p>2ch info - このスレッドは過去ログ倉庫に格納されています。 [{$marutori_ht}]</p>";
			
		}elseif( preg_match($naidesu_match, $read_response, $matches) ){ //<title>がそんな板orスレッドないです。
		
			if( preg_match($kakohtml_match, $read_response, $matches) ){
				$dat_response_status = "隊長! 過去ログ倉庫で、html化されたスレッドを発見しました。";
				$kakolog_uri = "http://{$this->host}/{$matches[1]}";
				$kakolog_url_en = urlencode($kakolog_uri);
				$read_kako_url="{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$ls}&amp;kakolog={$kakolog_url_en}&amp;kakoget=1";
				$dat_response_msg="<p>2ch info - 隊長! 過去ログ倉庫で、<a href=\"{$kakolog_uri}.html\"{$_conf['bbs_win_target']}>スレッド {$matches[3]}.html</a> を発見しました。 [<a href=\"{$read_kako_url}\">p2に取り込んで読む</a>]</p>";
				
			}elseif( preg_match($waithtml_match, $read_response, $matches) ){
				$dat_response_status="隊長! スレッドはhtml化されるのを待っているようです。";
				$marutori_ht="<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$ls}&amp;maru=true\">●IDでp2に取り込む</a>";
				$dat_response_msg="<p>2ch info - 隊長! スレッドはhtml化されるのを待っているようです。 [{$marutori_ht}]</p>";
				
			}else{
				if($_GET['kakolog']){
					$dat_response_status="そんな板orスレッドないです。";
					$kako_html_url=urldecode($_GET['kakolog']).".html";
					$read_kako_url="{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$ls}&amp;kakolog={$_GET['kakolog']}&amp;kakoget=1";
					$dat_response_msg = "<p>2ch info - そんな板orスレッドないです。</p>";
					$dat_response_msg .= "<p><a href=\"{$kako_html_url}\"{$_conf['bbs_win_target']}>{$kako_html_url}</a> [<a href=\"{$read_kako_url}\">p2にログを取り込んで読む</a>]</p>";
				}else{				
					$dat_response_status="そんな板orスレッドないです。";
					$dat_response_msg="<p>2ch info - そんな板orスレッドないです。</p>";
				}
			}
			
		} elseif ($_GET['kakolog']) {
			$dat_response_status="";
			$kako_html_url=urldecode($_GET['kakolog']).".html";
			$read_kako_url="{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$ls}&amp;kakolog={$_GET['kakolog']}&amp;kakoget=1";
			$dat_response_msg="<p><a href=\"{$kako_html_url}\"{$_conf['bbs_win_target']}>{$kako_html_url}</a> [<a href=\"{$read_kako_url}\">p2にログを取り込んで読む</a>]</p>";
		
		}
		
		return $dat_response_msg;
	}
	
	/**
	 * >>1のみをプレビューする
	 */
	function previewOne()
	{
		global $_conf, $dat_response_msg, $ls, $ptitle_ht, $fsockopen_time_limit, $_info_msg_ht;

		if (!($this->host && $this->bbs && $this->key)) {return false;}
		
		// ローカルdatから取得
		if (is_readable($this->keydat)) {
			$fd = fopen($this->keydat, "r");
			$first_line = fgets($fd, 32800);
			fclose ($fd);
			
			// be.2ch.net ならEUC→SJIS変換
			if (P2Util::isHostBe2chNet($this->host)) {
				include_once './strctl.class.php';
				$first_line = StrCtl::p2EUCtoSJIS($first_line);
			}
	
			$first_datline = rtrim($first_line);
			if (strstr($first_datline, "<>")) {
				$datline_sepa = "<>";
			} else {
				$datline_sepa = ",";
				$this->dat_type = "2ch_old";
			}
			$d = explode($datline_sepa, $first_datline);
			$this->ttitle = $d[4];
		}
		
		// ローカルdatなければオンラインから
		if (!$first_line) {
		
			$method = "GET";
			$url = "http://" . $this->host . "/{$this->bbs}/dat/{$this->key}.dat";
			
			$URL = parse_url($url); // URL分解
			if (isset($URL['query'])) { // クエリー
			    $URL['query'] = "?".$URL['query'];
			} else {
			    $URL['query'] = "";
			}
		
			//プロキシ
			if ($_conf['proxy_use']) {
				$send_host = $_conf['proxy_host'];
				$send_port = $_conf['proxy_port'];
				$send_path = $url;
			} else {
				$send_host = $URL['host'];
				$send_port = $URL['port'];
				$send_path = $URL['path'].$URL['query'];
			}
			
			if (!$send_port) {$send_port = 80;} // デフォルトを80
	
			$request = $method." ".$send_path." HTTP/1.0\r\n";
			$request .= "Host: ".$URL['host']."\r\n";
			$request .= "User-Agent: Monazilla/1.00 (".$_conf['p2name']."/".$_conf['p2version'].")"."\r\n";
			//$request .= "Range: bytes={$from_bytes}-\r\n";
			$request .= "Connection: Close\r\n";
			$request .= "\r\n";
			
			/* WEBサーバへ接続 */
			$fp = fsockopen($send_host, $send_port, $errno, $errstr, $fsockopen_time_limit);
			if (!$fp) {
				$url_t = P2Util::throughIme($url);
				$_info_msg_ht .= "<p>サーバ接続エラー: $errstr ($errno)<br>p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target']}>{$url}</a> に接続できませんでした。</p>";
				$this->diedat = true;
				return false;
			}
			
			fputs($fp, $request);
		
			while (!feof($fp)) {

				if ($start_here) {
				
					if ($code == "200") {
						$first_line = fgets($fp, 32800);
						break;
					} else {
						fclose($fp);
						return $this->previewOneNotFound();
					}
				} else {
					$l = fgets($fp,32800);
					//echo $l."<br>";// for debug
					if (preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) { // ex) HTTP/1.1 304 Not Modified
						$code = $matches[1];
						
						if ($code == "200") {
							;
						} else {
							fclose($fp);
							return $this->previewOneNotFound();							
						}

					} elseif (preg_match("/^Content-Length: ([0-9]+)/", $l, $matches)) {
						$onbytes = $matches[1];
					} elseif ($l == "\r\n") {
						$start_here = true;
					}
				}
				
			}
			fclose($fp);
			
			// be.2ch.net ならEUC→SJIS変換
			if (P2Util::isHostBe2chNet($this->host)) {
				include_once './strctl.class.php';
				$first_line = StrCtl::p2EUCtoSJIS($first_line);
			}

			$first_datline = rtrim($first_line);
			
			if (strstr($first_datline, "<>")) {
				$datline_sepa = "<>";
			} else {
				$datline_sepa = ",";
				$this->dat_type = "2ch_old";
			}
			$d = explode($datline_sepa, $first_datline);
			$this->ttitle = $d[4];
			
			$this->onthefly = true;
			
		} else {
			// 便宜上
			if (!$this->readnum) {
				$this->readnum = 1;
			}
		}

		$this->onthefly && $body .= "<div><span class=\"onthefly\">on the fly</span></div>";
		$body .= "<dl>";
		
		include_once("./showthread_class.inc"); // HTML表示クラス
		include_once("./showthreadpc_class.inc"); // HTML表示クラス
		$aShowThread = new ShowThreadPc($this);
		$body .= $aShowThread->transRes($first_line, 1); // 1を表示
		unset($aShowThread);
		
		$body .= "</dl>\n";
		return $body;
	}
	
	function previewOneNotFound()
	{
		global $dat_response_msg;
		if (P2Util::isHost2chs($this->host)) { // 2ch, bbspink ならread.cgiで確認
			$dat_response_msg = $this->get2chDatError();
		}
		$this->diedat = true;
		return false;
	}
	
	//============================================================================
	// $lsを分解してstartとtoとnofirstを求める
	//============================================================================
	function lsToPoint($ls)
	{
		global $_conf;

		$to = false;
		
		if (strstr($ls, "n")) {
			$nofirst = true;
			$ls = preg_replace("/n/", "", $ls);
		}

		$n = explode("-", $ls);
		if (sizeof($n)==1) {
			if (substr($n[0],0,1)=="l") {
				$nn = substr($n[0],1);
				if ($_conf['ktai']) {
					if ($nn > $_conf['k_rnum_range']) {
						$nn = $_conf['k_rnum_range'];
					}
				}
				$start = $this->rescount - $nn;
				if ($start < 1) { $start=1; }
				
				$to = $this->rescount;
			} elseif ($ls == "all") {
				$start = 1;
				$to = $this->rescount;
			} else {
				$start = $ls;
				$to = $ls;
				$nofirst = true;
			}
		} else {
			if (!$start = $n[0]) {
				$start = 1;
			}
			if (!$to = $n[1]) {
				$to = $this->rescount;
			}
		}
		
		// まとめ読みの表示数制限
		if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] > 0) {
			if ($start + $GLOBALS['rnum_all_range'] <= $to) {
				$to = $start + $GLOBALS['rnum_all_range'];
			}
			$GLOBALS['rnum_all_range'] = $GLOBALS['rnum_all_range'] - ($to - $start);
			$all_end = true;
		
		} else {
			// 携帯用の表示数制限
			if ($_conf['ktai']) {
				if ($start + $_conf['k_rnum_range'] <= $to) {
					$to = $start + $_conf['k_rnum_range'];
				}
			}
		}

		$this->resrange = array('start'=>$start,'to'=>$to,'nofirst'=>$nofirst);
		return $this->resrange;
	}
	
	/**
	 * Datを読み込む
	 * $this->datlines を set する
	 */
	function readDat($keydat)
	{
		global $_conf;
		
		if (file_exists($keydat)) {
			if ($this->datlines = @file($keydat)) {
			
				// be.2ch.net ならEUC→SJIS変換
				if (P2Util::isHostBe2chNet($this->host)) {
					include_once './strctl.class.php';
					$this->datlines = array_map(create_function('$str', 'return StrCtl::p2EUCtoSJIS($str);'), $this->datlines);
				}
			
				if (!strstr($this->datlines[0], "<>")) {
					$this->dat_type = "2ch_old";
				}
			}
		} else {
			return false;
		}
		$this->rescount = sizeof($this->datlines);
		
		if ($_conf['flex_idpopup']) {
			$this->setIdCount($this->datlines);
		}
		
		return $this->datlines;
	}

	/**
	 * 一つのスレ内でのID出現数をセットする
	 */
	function setIdCount($lines)
	{
		if ($lines) {
			foreach ($lines as $line) {
				$lar = explode('<>', $line);
				if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,10})|', $lar[2], $matches)) {
					$id = $matches[1];
					$this->idcount[$id]++;
				}
			}
		}
		return;
	}
	

	/**
	 * datlineをexplodeする
	 */
	function explodeDatLine($aline)
	{
		$aline = rtrim($aline);

		if ($this->dat_type == "2ch_old") {
			$parts = explode(',', $aline);
		} else {
			$parts = explode('<>', $aline);
		}
		
		return $parts;
	}

}
	
?>