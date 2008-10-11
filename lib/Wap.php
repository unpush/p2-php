<?php
/**
 * WWW Access on PHP
 * http://member.nifty.ne.jp/hippo2000/perltips/LWP.html を参考にしつつ似たような簡易のものを
 *
 * @author aki
 */

// 2005/04/20 aki このクラスは役割終了にして、PEAR利用に移行したい（HTTP_Clientなど）

// {{{ UserAgent

/**
 * UserAgent クラス
 *
 *  setAgent() : ua をセットする。
 *  setTimeout()
 *  request() : リクエストをサーバに送信して、レスポンスを返す。
 */
class UserAgent
{
    // {{{ properties

    private $_agent;  // User-Agent。アプリケーションの名前。
    private $_timeout;
    private $_maxRedirect;
    private $_redirectCount;
    private $_redirectCache;

    // }}}
    // {{{ constructor

    public function __construct()
    {
        $this->_agent = null;
        $this->_timeout = -1;
        $this->_maxRedirect = 3;
        $this->_redirectCount = 0;
        $this->_redirectCache = array();
    }

    // }}}
    // {{{ setAgent()

    /**
     * setAgent
     */
    public function setAgent($agent_name)
    {
        $this->_agent = $agent_name;
    }

    // }}}
    // {{{ setTimeout()

    /**
     * setTimeout
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;
    }

    // }}}
    // {{{ request()

    /**
     * request
     *
     * http://www.spencernetwork.org/memo/tips-3.php を参考にさせて頂きました。
     *
     * @param only_header bool 中身は取得せずにヘッダのみ取得する
     */
    public function request(Request $req, $only_header = false, $postdata_urlencode = true)
    {
        $res = new Response();

        $purl = parse_url($req->url); // URL分解
        if (isset($purl['query'])) { // クエリー
            $purl['query'] = "?".$purl['query'];
        } else {
            $purl['query'] = '';
        }
        $default_port = ($purl['scheme'] == 'https') ? 443 : 80; // デフォルトのポート

        // プロキシ
        if ($req->proxy) {
            $send_host = $req->proxy['host'];
            $send_port = isset($req->proxy['port']) ? $req->proxy['port'] : $default_port;
            $send_path = $req->url;
        } else {
            $send_host = $purl['host'];
            $send_port = isset($purl['port']) ? $purl['port'] : $default_port;
            $send_path = $purl['path'].$purl['query'];
        }

        // SSL
        if ($purl['scheme'] == 'https') {
            $send_host = 'ssl://' . $send_host;
        }

        $request = $req->method." ".$send_path." HTTP/1.0\r\n";
        $request .= "Host: ".$purl['host']."\r\n";
        if ($this->_agent) {
            $request .= "User-Agent: ".$this->_agent."\r\n";
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
        if (strtoupper($req->method) == 'POST') {
            // 通常はURLエンコードする
            if ($postdata_urlencode) {
                while (list($name, $value) = each($req->post)) {
                    $POST[] = $name . '=' . rawurlencode($value);
                }
                $postdata_content_type = 'application/x-www-form-urlencoded';

            // ●ログインのときなどはURLエンコードしない
            } else {
                while (list($name, $value) = each($req->post)) {
                    $POST[] = $name.'='.$value;
                }
                $postdata_content_type = 'text/plain';
            }
            $postdata = implode('&', $POST);
            $request .= 'Content-Type: '.$postdata_content_type."\r\n";
            $request .= 'Content-Length: '.strlen($postdata)."\r\n";
            $request .= "\r\n";
            $request .= $postdata;
        } else {
            $request .= "\r\n";
        }

        // WEBサーバへ接続
        if ($this->_timeout > 0) {
            $fp = fsockopen($send_host, $send_port, $errno, $errstr, $this->_timeout);
        } else {
            $fp = fsockopen($send_host, $send_port, $errno, $errstr);
        }

        if ($fp) {
            fputs($fp, $request);
            $body = '';
            $start_here = false;
            while (!feof($fp)) {

                if ($start_here) {
                    if ($only_header) {
                        break;
                    }
                    $body .= fread($fp, 4096);
                } else {
                    $l = fgets($fp,128000);
                    //echo $l."<br>"; //
                    // ex) HTTP/1.1 304 Not Modified
                    if (preg_match('/^(.+?): (.+)\r\n/', $l, $matches)) {
                        $res->headers[$matches[1]] = $matches[2];
                    } elseif (preg_match("/HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) {
                        $res->code = $matches[1];
                        $res->message = $matches[2];
                        $res->headers['HTTP'] = rtrim($l);
                    } elseif ($l == "\r\n") {
                        $start_here = true;
                    }
                }

            }

            fclose($fp);
            $res->content = $body;

            // リダイレクト(301 Moved, 302 Found)を追跡
            // RFC2616 - Section 10.3
            /*if ($GLOBALS['trace_http_redirect']) {
                if ($res->code == '301' || ($res->code == '302' && $req->isSafeMethod())) {
                    if (!$this->_redirectCache) {
                        $this->_maxRedirect   = 5;
                        $this->_redirectCount = 0;
                        $this->_redirectCache = array();
                    }
                    while ($res->is_redirect() && isset($res->headers['Location']) && $this->_redirectCount < $this->_maxRedirect) {
                        $this->_redirectCache[] = $res;
                        $req->setUrl($res->headers['Location']);
                        $res = $this->request($req);
                        $this->_redirectCount++;
                    }
                }
            } elseif ($res->is_redirect() && isset($res->headers['Location'])) {
                $res->message .= " (Location: <a href=\"{$res->headers['Location']}\">{$res->headers['Location']}</a>)";
            }*/

            return $res;

        } else {
            $res->code = $errno; // ex) 602
            $res->message = $errstr; // ex) "Connection Failed"
            return $res;
        }
    }

    // }}}
}

// }}}
// {{{ Request

/**
 * Request クラス
 */
class Request
{
    // {{{ properties

    public $method; // GET, POST, HEADのいずれか(デフォルトはGET、PUTはなし)
    public $url; // http://から始まるURL( http://user:pass@host:port/path?query )
    public $headers; // 任意の追加ヘッダ。文字列。
    public $content; // 任意のデータの固まり。
    public $post;    // POSTの時に送信するデータを格納した配列("変数名"=>"値")
    public $proxy; // ('host'=>"", 'port'=>"")

    public $modified;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->method = 'GET';
        $this->url = '';
        $this->headers = '';
        $this->content = false;
        $this->post = array();
        $this->proxy = array();
        $this->modified = false;
    }

    // }}}
    // {{{ setProxy()

    /**
     * setProxy
     */
    public function setProxy($host, $port)
    {
        $this->proxy['host'] = $host;
        $this->proxy['port'] = $port;
    }

    // }}}
    // {{{ setMethod()

    /**
     * setMethod
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    // }}}
    // {{{ setUrl()

    /**
     * setUrl
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    // }}}
    // {{{ setModified()

    /**
     * setModified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    // }}}
    // {{{ setHeaders()

    /**
     * setHeaders
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    // }}}
    // {{{ isSafeMethod()

    /**
     * isSafeMethod
     */
    public function isSafeMethod()
    {
        $method = strtoupper($this->method);
        // RFC2616 - Section 9
        if ($method == 'GET' || $method == 'HEAD'){
            return true;
        } else {
            return false;
        }
    }

    // }}}
}

// }}}
// {{{ Response

/**
 * Response クラス
 */
class Response
{
    // {{{ properties

    public $code; // リクエストの結果を示す数値
    public $message;  // codeに対応する人間が読める短い文字列。
    public $headers;    // 配列
    public $content; // 内容。任意のデータの固まり。

    // }}}
    // {{{ constructor()

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $code = false;
        $message = '';
        $content = false;
        $headers = array();
    }

    // }}}
    // {{{ is_success()

    /**
     * is_success
     */
    public function is_success()
    {
        if ($this->code == 200 || $this->code == 206 || $this->code == 304) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ is_error()

    /**
     * is_error
     */
    public function is_error()
    {
        if ($this->code == 200 || $this->code == 206 || $this->code == 304) {
            return false;
        } else {
            return true;
        }
    }

    // }}}
    // {{{ is_redirect()

    /**
     * is_redirect
     */
    public function is_redirect()
    {
        if ($this->code == 301 || $this->code == 302) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ HTTP Status Codes (note)
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
    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
