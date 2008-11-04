<?php
// WWW Access on PHP
// http://member.nifty.ne.jp/hippo2000/perltips/LWP.html を参考にしつつ似たような簡易のものを

// 2006/11/28 aki PEAR（HTTP_Requestなど）が使える時は、このクラスよりもPEARを優先利用したい

/**
 * WapUserAgent クラス
 */
class WapUserAgent
{
	var $agent = null;          // string   User-Agent
	var $timeout = null;        // integer  fsockopen() 時のタイムアウト秒
    var $atFsockopen = false;   // boolean  fsockopen() に@演算子を付けて、エラーを抑制するならtrue
	var $maxRedirect;
	var $redirectCount;
	var $redirectCache;
	
	/**
	 * setter agent
     *
     * @access  public
     * @return  void
	 */
	function setAgent($agent)
	{
		$this->agent = $agent;
	}
	
	/**
	 * setter timeout
     *
     * @access  public
     * @return  void
	 */
	function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}
	
	/**
	 * setter atFsockopen
     *
     * @access  public
     * @return  void
	 */
	function setAtFsockopen($atFsockopen)
	{
		$this->atFsockopen = $atFsockopen;
	}
	
    /**
	 * HTTPリクエストをサーバに送信して、ヘッダレスポンス（WapResponseオブジェクト）を取得する
     *
     * @access  public
     * @return  void
	 */
	function header($req)
	{
		return $this->request($req, array('onlyHeader' => true));
	}
    
	/**
	 * HTTPリクエストをサーバに送信して、レスポンス（WapResponseオブジェクト）を取得する
	 *
	 * @thanks http://www.spencernetwork.org/memo/tips-3.php
     *
     * @access  public
     * @param   array  $options
     * @return  WapResponse
	 */
	function request($req, $options = array())
	{
        if (!empty($options['onlyHeader'])) {
            $req->setOnlyHeader($options['onlyHeader']);
        }
        
		if (!$purl = parse_url($req->url)) {
            $res = new WapResponse;
            //$res->code = 
            $res->message = "parse_url() failed";
            return $res;
        }
		if (isset($purl['query'])) {
			$purl['query'] = "?" . $purl['query'];
		} else {
			$purl['query'] = '';
		}
		$default_port = ($purl['scheme'] == 'https') ? 443 : 80;

		// プロキシ
		if ($req->proxy) {
			$send_host = $req->proxy['host'];
			$send_port = isset($req->proxy['port']) ? $req->proxy['port'] : $default_port;
			$send_path = $req->url;
		} else {
			$send_host = $purl['host'];
			$send_port = isset($purl['port']) ? $purl['port'] : $default_port;
			$send_path = $purl['path'] . $purl['query'];
		}

		// SSL
		if ($purl['scheme'] == 'https') {
			$send_host = 'ssl://' . $send_host;
		}

		$request = $req->method . " " . $send_path . " HTTP/1.0\r\n";
		$request .= "Host: " . $purl['host'] . "\r\n";
		if ($this->agent) {
			$request .= "User-Agent: " . $this->agent . "\r\n";
		}
		$request .= "Connection: Close\r\n";
		//$request .= "Accept-Encoding: gzip\r\n";
		
		if ($req->modified) {
			$request .= "If-Modified-Since: {$req->modified}\r\n";
		}
		
		// Basic認証用のヘッダ
		if (isset($purl['user']) && isset($purl['pass'])) {
			$request .= "Authorization: Basic " . base64_encode($purl['user'] . ":" . $purl['pass']) . "\r\n";
		}

		// 追加ヘッダ
		if ($req->headers) {
			$request .= $req->headers;
		}
		
		// POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
		if (strtoupper($req->method) == 'POST') {
			// 通常はURLエンコードする
			if (empty($req->noUrlencodePost)) {
				while (list($name, $value) = each($req->post)) {
					$POST[] = $name . '=' . urlencode($value);
				}
				$postdata_content_type = 'application/x-www-form-urlencoded';

			// ●ログインのときなどはURLエンコードしない
			} else {
				while (list($name, $value) = each($req->post)) {
					$POST[] = $name . '=' . $value;
				}
				$postdata_content_type = 'text/plain';
			}
			$postdata = implode('&', $POST);
			$request .= 'Content-Type: ' . $postdata_content_type . "\r\n";
			$request .= 'Content-Length: ' . strlen($postdata) . "\r\n";
			$request .= "\r\n";
			$request .= $postdata;
		} else {
			$request .= "\r\n";
		}
	    
		$res = new WapResponse;
        
		// WEBサーバへ接続
		if ($this->timeout) {
            if ($this->atFsockopen) {
			    $fp = @fsockopen($send_host, $send_port, $errno, $errstr, $this->timeout);
		    } else {
                $fp = fsockopen($send_host, $send_port, $errno, $errstr, $this->timeout);
            }
        } else {
            if ($this->atFsockopen) {
			    $fp = @fsockopen($send_host, $send_port, $errno, $errstr);
		    } else {
                $fp = fsockopen($send_host, $send_port, $errno, $errstr);
            }
        }
        
		if (!$fp) {
			$res->code = $errno; // ex) 602
			$res->message = $errstr; // ex) "Connection Failed"
			return $res;
        }
        
		fputs($fp, $request);
		
        // header response
		while (!feof($fp)) {
			$l = fgets($fp, 8192);

			// ex) HTTP/1.1 304 Not Modified
			if (preg_match('/^(.+?): (.+)\r\n/', $l, $matches)) {
				$res->headers[$matches[1]] = $matches[2];
                
			} elseif (preg_match("/HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) {
				$res->code = $matches[1];
				$res->message = $matches[2];
				$res->headers['HTTP'] = rtrim($l);
                
			} elseif ($l == "\r\n") {
				break;
			}
        }
        
        // body response
        if (!$req->onlyHeader) {
            $body = '';
            while (!feof($fp)) {
    			$body .= fread($fp, 8192);
    		}
    		$res->setContent($body);
        }
        
		fclose($fp);
        
		// リダイレクト(301 Moved, 302 Found)を追跡
		// RFC2616 - Section 10.3
		/*if ($GLOBALS['trace_http_redirect']) {
			if ($res->code == '301' || ($res->code == '302' && $req->is_safe_method())) {
				if (empty($this->redirectCache)) {
					$this->maxRedirect   = 5;
					$this->redirectCount = 0;
					$this->redirectCache = array();
				}
				while ($res->is_redirect() && isset($res->headers['Location']) && $this->redirectCount < $this->maxRedirect) {
					$this->redirectCache[] = $res;
					$req->setUrl($res->headers['Location']);
					$res = $this->request($req);
					$this->redirectCount++;
				}
			}
		} elseif ($res->is_redirect() && isset($res->headers['Location'])) {
			$res->message .= " (Location: <a href=\"{$res->headers['Location']}\">{$res->headers['Location']}</a>)";
		}*/

		return $res;
	}

}


/**
 * WapRequest クラス
 */
class WapRequest
{
	var $method = 'GET';    // GET, POST, HEADのいずれか(デフォルトはGET、PUTはなし) 
	var $url = '';          // http://から始まるURL( http://user:pass@host:port/path?query )
	var $headers = '';      // 任意の追加ヘッダ。文字列。
	var $post = array();    // POSTの時に送信するデータを格納した配列("変数名"=>"値")
	var $proxy = array();   // ('host'=>"", 'port'=>"")
	var $modified = null;   // string  If-Modified-Since
    var $onlyHeader = false; // boolean ヘッダだけを取得するならtrue
    var $noUrlencodePost = false; // POSTデータをurlencodeしないならtrue。通常はurlencodeするのでtrue。
    
	/**
	 * @constructor
	 */
	function WapRequest()
	{
	}
    
	/**
	 * setter proxy
     *
     * @access  public
     * @return  void
	 */
	function setProxy($host, $port)
	{
		$this->proxy['host'] = $host;
		$this->proxy['port'] = $port;
	}
    
	/**
	 * setter method
     *
     * @access  public
     * @return  void
	 */
	function setMethod($method)
	{
		$this->method = $method;
	}
    
	/**
	 * setter url
     *
     * @access  public
     * @return  void
	 */
	function setUrl($url)
	{
		$this->url = $url;
	}
    
	/**
	 * setter modified
     *
     * @access  public
     * @return  void
	 */
	function setModified($modified)
	{
		$this->modified = $modified;
	}
	
    /**
	 * setter onlyHeader
     *
     * @access  public
     * @return  void
	 */
	function setOnlyHeader($onlyHeader)
	{
		$this->onlyHeader = $onlyHeader;
	}
    
    /**
	 * setter noUrlencodePost
     *
     * @access  public
     * @return  void
	 */
	function setNoUrlencodePost($noUrlencodePost)
	{
		$this->noUrlencodePost = $noUrlencodePost;
	}
    
	/**
	 * setter headers
     *
     * @access  public
     * @return  void
	 */
	function setHeaders($headers)
	{
		$this->headers = $headers;
	}

	/**
     * @access  public
     * @return  boolean
	 */
	function is_safe_method()
	{
		$method = strtoupper($this->method);
		// RFC2616 - Section 9
		if ($method == 'GET' || $method == 'HEAD'){
			return true;
		} else {
			return false;
		}
	}
}

/**
 * WapResponse クラス
 */
class WapResponse
{
	var $code    = null;    // リクエストの結果を示す数値
	var $message = '';      // codeに対応する人間が読める短い文字列。
	var $headers = array();	// 配列
	var $content = null;    // 内容。任意のデータの固まり。

	/**
	 * @constructor
	 */
	function WapResponse()
	{
	}
    
	/**
	 * setter content
     *
     * @access  public
     * @return  void
	 */
	function setContent($content)
	{
		$this->content = $content;
	}
    
	/**
     * @access  public
     * @return  boolean
	 */
	function is_success()
	{
		if ($this->code == 200 || $this->code == 206 || $this->code == 304) {
			return true;
		} else {
			return false;
		}
	}

	/**
     * @access  public
     * @return  boolean
	 */
	function is_error()
	{
		if (!$this->code or !$this->is_success()) {
			return true;
		} else {
			return false;
		}
	}

	/**
     * @access  public
     * @return  boolean
	 */
	function is_redirect()
	{
		if ($this->code == 301 || $this->code == 302) {
			return true;
		} else {
			return false;
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

