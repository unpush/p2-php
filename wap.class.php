<?php
// WWW Access on PHP
// http://member.nifty.ne.jp/hippo2000/perltips/LWP.html を参考にしつつ似たような簡易のものを

/**
 * UserAgent クラス
 *
 *	setAgent() : ua をセットする。
 *	setTimeout()
 *	request() : リクエストをサーバに送信して、レスポンスを返す。
 */
class UserAgent{
	/* 
	setAgent() : ua をセットする。
	setTimeout()
	request() : リクエストをサーバに送信して、レスポンスを返す。
	*/
	
	//=======================================
	var $agent;  // User-Agent。アプリケーションの名前。
	var $timeout;
	
	/**
	 * setAgent
	 */
	function setAgent($agent_name){
		$this->agent = $agent_name;
		return;
	}
	
	/**
	 * setTimeout
	 */
	function setTimeout($timeout)
	{
		$this->timeout = $timeout;
		return;
	}
	
	/**
	 * request
	 *
	 * http://www.spencernetwork.org/memo/tips-3.php を参考にさせて頂きました。
	 */
	function request($req)
	{
		$res = new Response;
		
		$purl = parse_url($req->url); // URL分解
		if (isset($purl['query'])) { // クエリー
		    $purl['query'] = "?".$purl['query'];
		} else {
		    $purl['query'] = "";
		}
	    if (!isset($purl['port'])){$purl['port'] = 80;} // デフォルトのポートは80
	
		// プロキシ
		if ($req->proxy) {
			$send_host = $req->proxy['host'];
			$send_port = $req->proxy['port'];
			$send_path = $req->url;
		} else {
			$send_host = $purl['host'];
			$send_port = $purl['port'];
			$send_path = $purl['path'].$purl['query'];
		}
	
		$request = $req->method." ".$send_path." HTTP/1.0\r\n";
		$request .= "Host: ".$purl['host']."\r\n";
		if ($this->agent) {
			$request .= "User-Agent: ".$this->agent."\r\n";
		}
		$request .= "Connection: Close\r\n";
		//$request .= "Accept-Encoding: gzip\r\n";
		
		if ($req->modified) {
			$request .= "If-Modified-Since: {$req->modified}\r\n";
		}
		
		// Basic認証用のヘッダ
		if (isset($purl['user']) && isset($purl['pass'])) {
		    $request .= "Authorization: Basic ".base64_encode($purl['user'].":".$purl['pass'])."\r\n";
		}

		// 追加ヘッダ
		if ($req->headers) {
	    	$request .= $req->headers;
		}
		
		// POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
		if (strtoupper($req->method) == "POST") {
		    while (list($name, $value) = each($req->post)) {
		        $POST[] = $name."=".urlencode($value);
		    }
		    $postdata = implode("&", $POST);
		    $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		    $request .= "Content-Length: ".strlen($postdata)."\r\n";
		    $request .= "\r\n";
		    $request .= $postdata;
		} else {
		    $request .= "\r\n";
		}
	
		// WEBサーバへ接続
		if ($this->timeout) {
			$fp = fsockopen($send_host, $send_port, $errno, $errstr, $this->timeout);
		} else {
			$fp = fsockopen($send_host, $send_port, $errno, $errstr);
		}
		
		if ($fp) {
			fputs($fp, $request);
			$body = "";
			while (!feof($fp)) {
			
				if ($start_here) {
					$body .= fread($fp, 4096);
				} else {
					$l = fgets($fp,128000);
					//echo $l."<br>"; //
					if( preg_match("/HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches) ){ // ex) HTTP/1.1 304 Not Modified
						$res->code = $matches[1];
						$res->message = $matches[2];
					}elseif($l=="\r\n"){
						$start_here = true;
					}
				}
				
			}
			
			fclose ($fp);
			$res->content = $body;
			return $res;
			
		}else{
			$res->code = $errno; //602
			$res->message = $errstr; //"Connection Failed"
			return $res;
		}
	}

}

//======================================================
// Request クラス
//======================================================
class Request{

	var $method; //GET, POST, HEADのいずれか(デフォルトはGET、PUTはなし) 
	var $url; //http://から始まるURL( http://user:pass@host:port/path?query )
	var $headers; //任意の追加ヘッダ。
	var $content; // 任意のデータの固まり。
	var $post;    // POSTの時に送信するデータを格納した配列("変数名"=>"値")
	var $proxy; // ('host'=>"", 'port'=>"")
	
	var $modified;
	
	//===============================
	function Request(){
		$this->method = "GET";
		$this->url = "";
		$this->headers = "";
		$this->content = false;
		$this->post = array();
		$this->modified = false;
		$this->proxy = array();
	}
	
	function setProxy($host, $port){
		$this->proxy['host'] = $host;
		$this->proxy['port'] = $port;
		return;
	}
	
	function setMethod($method){
		$this->method = $method;
		return;
	}
	
	function setUrl($url){
		$this->url = $url;
		return;
	}

	function setModified($modified){
		$this->modified = $modified;
		return;
	}

	function setHeaders($headers){
		$this->headers = $headers;
		return;
	}

}

//======================================================
// Response クラス
//======================================================
class Response{

	var $code; //リクエストの結果を示す数値
	var $message;  // codeに対応する人間が読める短い文字列。
	var $headers;
	var $content; // 内容。任意のデータの固まり。
	
	function Response(){
		$code = false;
		$message = "";
		$content = false;
	}
	
	function is_success(){
		if($this->code == 200 || $this->code == 206 || $this->code == 304){
			return true;
		}else{
			return false;
		}
	}

	function is_error(){
		if($this->code == 200 || $this->code == 206 || $this->code == 304){
			return false;
		}else{
			return true;
		}
	}
	
/*
    000, 'Unknown Error',
    200, 'OK',
    201, 'CREATED',
    202, 'Accepted',
    203, 'Partial Information',
    204, 'No Response',
	206, 'Partial Content',
    301, 'Moved',
    302, 'Found',
    303, 'Method',
    304, 'Not Modified',
    400, 'Bad Request',
    401, 'Unauthorized',
    402, 'Payment Required',
    403, 'Forbidden',
    404, 'Not Found',
    500, 'Internal Error',
    501, 'Not Implemented',
    502, 'Bad Response',
    503, 'Too Busy',
    600, 'Bad Request in Client',
    601, 'Not Implemented in Client',
    602, 'Connection Failed',
    603, 'Timed Out',
*/

}

?>